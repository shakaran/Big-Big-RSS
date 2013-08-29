<?php

class Pref_System extends Handler_Protected {

	function __construct($link, $args) {
		parent::__construct($link, $args);
	}

	function before($method) {
		if (parent::before($method)) {
			if ($_SESSION["access_level"] < 10) {
				print __("Your access level is insufficient to open this tab.");
				return false;
			}
			return true;
		}
		return false;
	}

	function csrf_ignore($method) {
		$csrf_ignored = array("index");

		return array_search($method, $csrf_ignored) !== false;
	}

	function index() {

		print "<div dojoType=\"dijit.layout.AccordionContainer\" region=\"center\">";
		print "<div dojoType=\"dijit.layout.AccordionPane\" title=\"".__('Error Log')."\">";

		$result = db_query($this->link, "SELECT errno, errstr, filename, lineno,
			created_at, login FROM ttrss_error_log
			LEFT JOIN ttrss_users ON (owner_uid = ttrss_users.id)
			ORDER BY ttrss_error_log.id DESC
			LIMIT 100");

		print "<p><table width=\"100%\" cellspacing=\"10\" class=\"prefErrorLog\">";

		print "<tr class=\"title\">
			<td width='5%'>".__("Error")."</td>
			<td>".__("Filename")."</td>
			<td>".__("Message")."</td>
			<td width='5%'>".__("User")."</td>
			<td width='5%'>".__("Date")."</td>
			</tr>";

		while ($line = db_fetch_assoc($result)) {
			print "<tr class=\"errrow\">";

			foreach ($line as $k => $v) {
				$line[$k] = htmlspecialchars($v);
			}

			print "<td class='errno'>" . Logger::$errornames[$line["errno"]] . " (" . $line["errno"] . ")</td>";
			print "<td class='filename'>" . $line["filename"] . ":" . $line["lineno"] . "</td>";
			print "<td class='errstr'>" . $line["errstr"] . "</td>";
			print "<td class='login'>" . $line["login"] . "</td>";

			print "<td class='timestamp'>" .
				make_local_datetime($this->link,
				$line["created_at"], false) . "</td>";

			print "</tr>";
		}

		print "</table>";

		print "</div>";

		global $pluginhost;
		$pluginhost->run_hooks($pluginhost::HOOK_PREFS_TAB,
			"hook_prefs_tab", "prefSystem");

		print "</div>"; #container
	}

}
?>
