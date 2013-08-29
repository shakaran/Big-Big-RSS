<?php
class PluginHost {
	private $link;
	private $hooks = array();
	private $plugins = array();
	private $handlers = array();
	private $commands = array();
	private $storage = array();
	private $feeds = array();
	private $api_methods = array();
	private $owner_uid;
	private $debug;

	const HOOK_ARTICLE_BUTTON = 1;
	const HOOK_ARTICLE_FILTER = 2;
	const HOOK_PREFS_TAB = 3;
	const HOOK_PREFS_TAB_SECTION = 4;
	const HOOK_PREFS_TABS = 5;
	const HOOK_FEED_PARSED = 6;
	const HOOK_UPDATE_TASK = 7;
	const HOOK_AUTH_USER = 8;
	const HOOK_HOTKEY_MAP = 9;
	const HOOK_RENDER_ARTICLE = 10;
	const HOOK_RENDER_ARTICLE_CDM = 11;
	const HOOK_FEED_FETCHED = 12;
	const HOOK_SANITIZE = 13;
	const HOOK_RENDER_ARTICLE_API = 14;
	const HOOK_TOOLBAR_BUTTON = 15;
	const HOOK_ACTION_ITEM = 16;
	const HOOK_HEADLINE_TOOLBAR_BUTTON = 17;
	const HOOK_HOTKEY_INFO = 18;
	const HOOK_ARTICLE_LEFT_BUTTON = 19;

	const KIND_ALL = 1;
	const KIND_SYSTEM = 2;
	const KIND_USER = 3;

	function __construct($link) {
		$this->link = $link;

		$this->storage = $_SESSION["plugin_storage"];

		if (!$this->storage) $this->storage = array();
	}

	private function register_plugin($name, $plugin) {
		//array_push($this->plugins, $plugin);
		$this->plugins[$name] = $plugin;
	}

	function get_link() {
		return $this->link;
	}

	function get_plugins() {
		return $this->plugins;
	}

	function get_plugin($name) {
		return $this->plugins[$name];
	}

	function run_hooks($type, $method, $args) {
		foreach ($this->get_hooks($type) as $hook) {
			$hook->$method($args);
		}
	}

	function add_hook($type, $sender) {
		if (!is_array($this->hooks[$type])) {
			$this->hooks[$type] = array();
		}

		array_push($this->hooks[$type], $sender);
	}

	function del_hook($type, $sender) {
		if (is_array($this->hooks[$type])) {
			$key = array_Search($this->hooks[$type], $sender);
			if ($key !== FALSE) {
				unset($this->hooks[$type][$key]);
			}
		}
	}

	function get_hooks($type) {
		if (isset($this->hooks[$type])) {
			return $this->hooks[$type];
		} else {
			return array();
		}
	}
	function load_all($kind, $owner_uid = false) {
		$plugins = array_map("basename", glob("plugins/*"));
		$this->load(join(",", $plugins), $kind, $owner_uid);
	}

	function load($classlist, $kind, $owner_uid = false) {
		$plugins = explode(",", $classlist);

		$this->owner_uid = (int) $owner_uid;

		foreach ($plugins as $class) {
			$class = trim($class);
			$class_file = strtolower(basename($class));
			$file = dirname(__FILE__)."/../plugins/$class_file/init.php";

			if (!isset($this->plugins[$class])) {
				if (file_exists($file)) require_once $file;

				if (class_exists($class) && is_subclass_of($class, "Plugin")) {
					$plugin = new $class($this);

					switch ($kind) {
					case $this::KIND_SYSTEM:
						if ($this->is_system($plugin)) {
							$plugin->init($this);
							$this->register_plugin($class, $plugin);
						}
						break;
					case $this::KIND_USER:
						if (!$this->is_system($plugin)) {
							$plugin->init($this);
							$this->register_plugin($class, $plugin);
						}
						break;
					case $this::KIND_ALL:
						$plugin->init($this);
						$this->register_plugin($class, $plugin);
						break;
					}
				}
			}
		}
	}

	function is_system($plugin) {
		$about = $plugin->about();

		return @$about[3];
	}

	// only system plugins are allowed to modify routing
	function add_handler($handler, $method, $sender) {
		$handler = str_replace("-", "_", strtolower($handler));
		$method = strtolower($method);

		if ($this->is_system($sender)) {
			if (!is_array($this->handlers[$handler])) {
				$this->handlers[$handler] = array();
			}

			$this->handlers[$handler][$method] = $sender;
		}
	}

	function del_handler($handler, $method) {
		$handler = str_replace("-", "_", strtolower($handler));
		$method = strtolower($method);

		if ($this->is_system($sender)) {
			unset($this->handlers[$handler][$method]);
		}
	}

	function lookup_handler($handler, $method) {
		$handler = str_replace("-", "_", strtolower($handler));
		$method = strtolower($method);

		if (is_array($this->handlers[$handler])) {
			if (isset($this->handlers[$handler]["*"])) {
				return $this->handlers[$handler]["*"];
			} else {
				return $this->handlers[$handler][$method];
			}
		}

		return false;
	}

	function add_command($command, $description, $sender, $suffix = "", $arghelp = "") {
		$command = str_replace("-", "_", strtolower($command));

		$this->commands[$command] = array("description" => $description,
			"suffix" => $suffix,
			"arghelp" => $arghelp,
			"class" => $sender);
	}

	function del_command($command) {
		$command = "-" . strtolower($command);

		unset($this->commands[$command]);
	}

	function lookup_command($command) {
		$command = "-" . strtolower($command);

		if (is_array($this->commands[$command])) {
			return $this->commands[$command]["class"];
		} else {
			return false;
		}

		return false;
	}

	function get_commands() {
		return $this->commands;
	}

	function run_commands($args) {
		foreach ($this->get_commands() as $command => $data) {
			if (isset($args[$command])) {
				$command = str_replace("-", "", $command);
				$data["class"]->$command($args);
			}
		}
	}

	function load_data($force = false) {
		if ($this->owner_uid && (!$_SESSION["plugin_storage"] || $force))  {
			$plugin = db_escape_string($this->link, $plugin);

			$result = db_query($this->link, "SELECT name, content FROM ttrss_plugin_storage
				WHERE owner_uid = '".$this->owner_uid."'");

			while ($line = db_fetch_assoc($result)) {
				$this->storage[$line["name"]] = unserialize($line["content"]);
			}

			$_SESSION["plugin_storage"] = $this->storage;
		}
	}

	private function save_data($plugin) {
		if ($this->owner_uid) {
			$plugin = db_escape_string($this->link, $plugin);

			db_query($this->link, "BEGIN");

			$result = db_query($this->link,"SELECT id FROM ttrss_plugin_storage WHERE
				owner_uid= '".$this->owner_uid."' AND name = '$plugin'");

			if (!isset($this->storage[$plugin]))
				$this->storage[$plugin] = array();

			$content = db_escape_string($this->link, serialize($this->storage[$plugin]));

			if (db_num_rows($result) != 0) {
				db_query($this->link, "UPDATE ttrss_plugin_storage SET content = '$content'
					WHERE owner_uid= '".$this->owner_uid."' AND name = '$plugin'");

			} else {
				db_query($this->link, "INSERT INTO ttrss_plugin_storage
					(name,owner_uid,content) VALUES
					('$plugin','".$this->owner_uid."','$content')");
			}

			db_query($this->link, "COMMIT");
		}
	}

	function set($sender, $name, $value, $sync = true) {
		$idx = get_class($sender);

		if (!isset($this->storage[$idx]))
			$this->storage[$idx] = array();

		$this->storage[$idx][$name] = $value;

		$_SESSION["plugin_storage"] = $this->storage;

		if ($sync) $this->save_data(get_class($sender));
	}

	function get($sender, $name, $default_value = false) {
		$idx = get_class($sender);

		if (isset($this->storage[$idx][$name])) {
			return $this->storage[$idx][$name];
		} else {
			return $default_value;
		}
	}

	function get_all($sender) {
		$idx = get_class($sender);

		return $this->storage[$idx];
	}

	function clear_data($sender) {
		if ($this->owner_uid) {
			$idx = get_class($sender);

			unset($this->storage[$idx]);

			db_query($this->link, "DELETE FROM ttrss_plugin_storage WHERE name = '$idx'
				AND owner_uid = " . $this->owner_uid);

			$_SESSION["plugin_storage"] = $this->storage;
		}
	}

	function set_debug($debug) {
		$this->debug = $debug;
	}

	function get_debug() {
		return $this->debug;
	}

	// Plugin feed functions are *EXPERIMENTAL*!

	// cat_id: only -1 is supported (Special)
	function add_feed($cat_id, $title, $icon, $sender) {
		if (!$this->feeds[$cat_id]) $this->feeds[$cat_id] = array();

		$id = count($this->feeds[$cat_id]);

		array_push($this->feeds[$cat_id],
			array('id' => $id, 'title' => $title, 'sender' => $sender, 'icon' => $icon));

		return $id;
	}

	function get_feeds($cat_id) {
		return $this->feeds[$cat_id];
	}

	// convert feed_id (e.g. -129) to pfeed_id first
	function get_feed_handler($pfeed_id) {
		foreach ($this->feeds as $cat) {
			foreach ($cat as $feed) {
				if ($feed['id'] == $pfeed_id) {
					return $feed['sender'];
				}
			}
		}
	}

	static function pfeed_to_feed_id($label) {
		return PLUGIN_FEED_BASE_INDEX - 1 - abs($label);
	}

	static function feed_to_pfeed_id($feed) {
		return PLUGIN_FEED_BASE_INDEX - 1 + abs($feed);
	}

	function add_api_method($name, $sender) {
		if ($this->is_system($sender)) {
			$this->api_methods[strtolower($name)] = $sender;
		}
	}

	function get_api_method($name) {
		return $this->api_methods[$name];
	}
}