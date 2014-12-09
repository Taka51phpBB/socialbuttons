<?php
/**
*
* @package phpBB Extension - tas2580 Social Media Buttons
* @copyright (c) 2014 tas2580 (https://tas2580.net)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace tas2580\socialbuttons\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\template\template */
	protected $template;

	/**
	* Constructor
	*
	* @param \phpbb\config\config $config
	* @param \phpbb\template\template $template
	* @access public
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\template\template $template)
	{
		$this->config = $config;
		$this->template = $template;
	}

	/**
	* Assign functions defined in this class to event listeners in the core
	*
	* @return array
	* @static
	* @access public
	*/
	static public function getSubscribedEvents()
	{
		return array(
			'core.viewtopic_modify_page_title'		=> 'display_socialbuttons',
			'core.user_setup'          				=> 'load_language_on_setup',
		);
	}

	/**
	* Load Social Media Buttons
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function display_socialbuttons($event)
	{
		global $config;
		$url = generate_board_url() . '/viewtopic.php?f=' . $event['topic_data']['forum_id'] . '&t=' . $event['topic_data']['topic_id'];
		$shares = $this->get_share_count($url);
		$position = isset($config['socialbuttons_position']) ? $config['socialbuttons_position'] : 2;
		$this->template->assign_vars(array(
			'TOPIC_TITLE'		=> $event['topic_data']['topic_title'],
			'U_TOPIC'			=> $url,
			'SHARES_FACEBOOK'	=> (int) $shares['facebook'],
			'SHARES_TWITTER'	=> (int) $shares['twitter'],
			'SHARES_GOOGLE'		=> (int) $shares['google'],
			'SHARES_LINKEDIN'	=> (int) $shares['linkedin'],
			'S_FACEBOOK'		=> isset($config['socialbuttons_facebook']) ? $config['socialbuttons_facebook'] : '',
			'S_TWITTER'			=> isset($config['socialbuttons_twitter']) ? $config['socialbuttons_twitter'] : '',
			'S_GOOGLE'			=> isset($config['socialbuttons_google']) ? $config['socialbuttons_google'] : '',
			'S_LINKEDIN'		=> isset($config['socialbuttons_linkedin']) ? $config['socialbuttons_linkedin'] : '',
			'S_SHOW_AT_TOP'		=> ($position == 0 || $position == 1) ? true : false,
			'S_SHOW_AT_BOTTOM'	=> ($position == 0 || $position == 2) ? true : false,
		));
	}


	function get_share_count($url)
	{
		global $phpbb_root_path, $config;
		$shares = array();
		$cache_time = isset($config['socialbuttons_cachetime']) ? $config['socialbuttons_cachetime'] : 0;
		$multiplicator = isset($config['socialbuttons_multiplicator']) ? $config['socialbuttons_multiplicator'] : 1;

		$cachetime = ((int) $cache_time * (int) $multiplicator);
		$cache_file = $phpbb_root_path . 'cache/' . md5($url) . '.json';
		$filetime = file_exists($cache_file) ? filemtime($cache_file) : 0;


		if(($filetime == 0) || ($filetime < (time() - $cachetime)))
		{
			if(isset($config['socialbuttons_facebook']) && ($config['socialbuttons_facebook'] == 1))
			{
				if($pageinfo = json_decode(@file_get_contents("https://graph.facebook.com/" . $url), true))
				{
					$shares['facebook'] = $pageinfo['shares'];
				}
			}
			if(isset($config['socialbuttons_twitter']) && ($config['socialbuttons_twitter'] == 1))
			{
				if($pageinfo = json_decode(@file_get_contents("https://cdn.api.twitter.com/1/urls/count.json?url=" . $url), true))
				{
					$shares['twitter'] = $pageinfo['count'];
				}
			}
			if(isset($config['socialbuttons_google']) && ($config['socialbuttons_google'] == 1))
			{
				if($data = @file_get_contents("https://plusone.google.com/_/+1/fastbutton?url=" . $url))
				{
					preg_match('#<div id="aggregateCount" class="Oy">([0-9]+)</div>#s', $data, $matches);
					$shares['google'] = $matches[1];
				}
			}
			if(isset($config['socialbuttons_linkedin']) && ($config['socialbuttons_linkedin'] == 1))
			{
				if($pageinfo = json_decode(@file_get_contents('http://www.linkedin.com/countserv/count/share?url=' . $url . '&format=json'), true))
				{
					$shares['linkedin'] = $pageinfo['count'];
				}
			}
			$json = json_encode($shares);
			$handle = fopen($cache_file, 'w');
			fwrite($handle, $json);
			fclose($handle);
		}
		else
		{
			$json = file_get_contents($cache_file);
			$shares = json_decode($json, true);
		}
		return $shares;
	}


	/**
	* Add custom language variables
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
    public function load_language_on_setup($event)
    {
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = array(
            'ext_name' => 'tas2580/socialbuttons',
            'lang_set' => 'common',
        );
        $event['lang_set_ext'] = $lang_set_ext;
    }
}
