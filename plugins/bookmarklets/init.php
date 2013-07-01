<?php
class Bookmarklets extends Plugin 
{
	private $link;
	private $host;

	function about() 
	{
		return array(1.0, 'Easy feed subscription and web page sharing using bookmarklets', 'fox');
	}

	function init($host) 
	{
		$this->link = $host->get_link();
		$this->host = $host;

		$host->add_hook($host::HOOK_PREFS_TAB, $this);
	}

	function hook_prefs_tab($args) 
	{
		if ($args == 'prefFeeds') 
		{

		    $bm_subscribe_url = str_replace('%s', '', add_feed_url());
		    $confirm_str = str_replace("'", "\'", __('Subscribe to %s in Big Big RSS?')); // @todo Use Config.php for program name
		    
		    $suscribe_javascript = "javascript:{
		    if(confirm('$confirm_str'.replace('%s',window.location.href)))
		        window.location.href='$bm_subscribe_url'+ window.location.hostname + window.location.pathname
		    }";
		    
		    $bm_url = htmlspecialchars($suscribe_javascript);
		    
		    $bm_share_url = htmlspecialchars(
		        "javascript:(function(){var d=document,w=window,e=w.getSelection,k=d.getSelection,x=d.selection,s=(e?e():(k)?k():(x?x.createRange().text:0)),f='" . SELF_URL_PATH
		        . "/public.php?op=sharepopup',l=d.location,e=encodeURIComponent,g=f+'&title='+((e(s))?e(s):e(document.title))+'&url='+e(l.href);function a(){if(!w.open(g,'t','toolbar=0,resizable=0,scrollbars=1,status=1,width=500,height=250')){l.href=g;}}a();})()");
		    
			echo '<div dojoType="dijit.layout.AccordionPane" title="' . __('Bookmarklets') . '">' .
                 '<p>' . __('Drag the link below to your browser toolbar, open the feed you are interested in in your browser and click on the link to subscribe to it.') . '</p>' .
                 '<a href="' . $bm_url . '" class="bookmarklet">' . __('Subscribe in Big Big RSS') . '</a>' .  // @todo Use Config.php for program name
			     '<p>' . __('Use this bookmarklet to publish arbitrary pages using Big Big RSS') . '</p>' .
                 '<a href="' . $bm_share_url . '" class="bookmarklet">' . __('Share with Big Big RSS') . '</a>' .
			     '</div>'; #pane
		}
	}
}
