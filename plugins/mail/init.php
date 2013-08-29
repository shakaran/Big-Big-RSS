<?php
class Mail extends Plugin {

	private $link;
	private $host;

	function about() {
		return array(1.0,
			"Share article via email",
			"fox");
	}

	function init($host) {
		$this->link = $host->get_link();
		$this->host = $host;

		$host->add_hook($host::HOOK_ARTICLE_BUTTON, $this);
	}

	function get_js() {
		return file_get_contents(dirname(__FILE__) . "/mail.js");
	}

	function hook_article_button($line) {
		return "<img src=\"plugins/mail/mail.png\"
					class='tagsPic' style=\"cursor : pointer\"
					onclick=\"emailArticle(".$line["id"].")\"
					alt='Zoom' title='".__('Forward by email')."'>";
	}

	function emailArticle() {

		$param = db_escape_string($this->link, $_REQUEST['param']);

		print "<input dojoType=\"dijit.form.TextBox\" style=\"display : none\" name=\"op\" value=\"pluginhandler\">";
		print "<input dojoType=\"dijit.form.TextBox\" style=\"display : none\" name=\"plugin\" value=\"mail\">";
		print "<input dojoType=\"dijit.form.TextBox\" style=\"display : none\" name=\"method\" value=\"sendEmail\">";

		$result = db_query($this->link, "SELECT email, full_name FROM ttrss_users WHERE
			id = " . $_SESSION["uid"]);

		$user_email = htmlspecialchars(db_fetch_result($result, 0, "email"));
		$user_name = htmlspecialchars(db_fetch_result($result, 0, "full_name"));

		if (!$user_name) $user_name = $_SESSION['name'];

		print "<input dojoType=\"dijit.form.TextBox\" style=\"display : none\" name=\"from_email\" value=\"$user_email\">";
		print "<input dojoType=\"dijit.form.TextBox\" style=\"display : none\" name=\"from_name\" value=\"$user_name\">";

		require_once "lib/MiniTemplator.class.php";

		$tpl = new MiniTemplator;
		$tpl_t = new MiniTemplator;

		$tpl->readTemplateFromFile("templates/email_article_template.txt");

		$tpl->setVariable('USER_NAME', $_SESSION["name"], true);
		$tpl->setVariable('USER_EMAIL', $user_email, true);
		$tpl->setVariable('TTRSS_HOST', $_SERVER["HTTP_HOST"], true);

		$result = db_query($this->link, "SELECT link, content, title
			FROM ttrss_user_entries, ttrss_entries WHERE id = ref_id AND
			id IN ($param) AND owner_uid = " . $_SESSION["uid"]);

		if (db_num_rows($result) > 1) {
			$subject = __("[Forwarded]") . " " . __("Multiple articles");
		}

		while ($line = db_fetch_assoc($result)) {

			if (!$subject)
				$subject = __("[Forwarded]") . " " . htmlspecialchars($line["title"]);

			$tpl->setVariable('ARTICLE_TITLE', strip_tags($line["title"]));
			$tpl->setVariable('ARTICLE_URL', strip_tags($line["link"]));

			$tpl->addBlock('article');
		}

		$tpl->addBlock('email');

		$content = "";
		$tpl->generateOutputToString($content);

		print "<table width='100%'><tr><td>";

		print __('From:');

		print "</td><td>";

		print "<input dojoType=\"dijit.form.TextBox\" disabled=\"1\" style=\"width : 30em;\"
				value=\"$user_name <$user_email>\">";

		print "</td></tr><tr><td>";

		print __('To:');

		print "</td><td>";

		print "<input dojoType=\"dijit.form.ValidationTextBox\" required=\"true\"
				style=\"width : 30em;\"
				name=\"destination\" id=\"emailArticleDlg_destination\">";

		print "<div class=\"autocomplete\" id=\"emailArticleDlg_dst_choices\"
				style=\"z-index: 30; display : none\"></div>";

		print "</td></tr><tr><td>";

		print __('Subject:');

		print "</td><td>";

		print "<input dojoType=\"dijit.form.ValidationTextBox\" required=\"true\"
				style=\"width : 30em;\"
				name=\"subject\" value=\"$subject\" id=\"subject\">";

		print "</td></tr>";

		print "<tr><td colspan='2'><textarea dojoType=\"dijit.form.SimpleTextarea\" style='font-size : 12px; width : 100%' rows=\"20\"
			name='content'>$content</textarea>";

		print "</td></tr></table>";

		print "<div class='dlgButtons'>";
		print "<button dojoType=\"dijit.form.Button\" onclick=\"dijit.byId('emailArticleDlg').execute()\">".__('Send e-mail')."</button> ";
		print "<button dojoType=\"dijit.form.Button\" onclick=\"dijit.byId('emailArticleDlg').hide()\">".__('Cancel')."</button>";
		print "</div>";

		//return;
	}

	function sendEmail() {
		require_once 'classes/ttrssmailer.php';

		$reply = array();

		$mail = new ttrssMailer();

		$mail->From = strip_tags($_REQUEST['from_email']);
		$mail->FromName = strip_tags($_REQUEST['from_name']);
		$mail->AddAddress($_REQUEST['destination']);

		$mail->IsHTML(false);
		$mail->Subject = $_REQUEST['subject'];
		$mail->Body = $_REQUEST['content'];

		$rc = $mail->Send();

		if (!$rc) {
			$reply['error'] =  $mail->ErrorInfo;
		} else {
			save_email_address($this->link, db_escape_string($this->link, $destination));
			$reply['message'] = "UPDATE_COUNTERS";
		}

		print json_encode($reply);
	}

	function completeEmails() {
		$search = db_escape_string($this->link, $_REQUEST["search"]);

		print "<ul>";

		foreach ($_SESSION['stored_emails'] as $email) {
			if (strpos($email, $search) !== false) {
				print "<li>$email</li>";
			}
		}

		print "</ul>";
	}


}
