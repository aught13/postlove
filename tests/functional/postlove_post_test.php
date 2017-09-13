<?php
/**
*
* Postlove Control test
*
* @copyright (c) 2014 Stanislav Atanasov
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace aught13\postlove\tests\functional;

/**
* @group functional
*/
class postlove_post_test extends postlove_base
{
	protected $post2 = array();
	private $tpic;
	private $pst;
	public function test_post()
	{
		include('CssParser.php');
		$parser = new CssParser();
		$parser->load_file('/home/travis/build/phpBB3/phpBB/ext/aught13/postlove/styles/all/theme/default.css');
		$parser->parse();
		
		$this->login();
		
		// Test creating topic and post to test
		$post = $this->create_topic(2, 'Test Topic 1', 'This is a test topic posted by the testing framework.');
		$crawler = self::request('GET', "viewtopic.php?t={$post['topic_id']}&sid={$this->sid}");
		
		$post2 = $this->create_post(2, $post['topic_id'], 'Re: Test Topic 1', 'This is a test [b]post[/b] posted by the testing framework.');
		$crawler = self::request('GET', "viewtopic.php?t={$post2['topic_id']}&sid={$this->sid}");
		$this->tpic = $post2['topic_id'];
		$this->pst = $post2['post_id'];
		
		//Do we see the static?
		$class = $crawler->filter('#p' . $post2['post_id'])->filter('.postlove')->filter('span')->attr('class');

		$this->assertContains('heart-red-16.png', $parser->parsed['main']['.' . $class]['background']);
		// error above PHPUnit_Framework_Exception: Argument #2 (No Value) of PHPUnit_Framework_Assert::assertContains() must be a array, traversable or string
		$this->assertContains('0', $crawler->filter('#p' . $post2['post_id'])->filter('.postlove_likers')->filter('span')->attr('title'));
		
		//toggle like
		$url = $crawler->filter('#p' . $post2['post_id'])->filter('.postlove')->filter('a')->attr('href');
		$crw1 = self::request('GET', substr($url, 1), array(), array(), array('CONTENT_TYPE'	=> 'application/json'));
		
		//reload page and test ...
		$crawler = self::request('GET', "viewtopic.php?t={$post2['topic_id']}&sid={$this->sid}");
		$class = $crawler->filter('#p' . $post2['post_id'])->filter('.postlove')->filter('span')->attr('class');

		$this->assertContains('heart-white-16.png', $parser->parsed['main']['.' . $class]['background']);
		$this->assertContains('1', $crawler->filter('#p' . $post2['post_id'])->filter('.postlove_likers')->filter('span')->attr('title'));
		$this->logout();
	}
	
	public function test_guest_see_loves()
	{
		$crawler = self::request('GET', "viewtopic.php?t=" . $this->tpic);
		$this->assertContains('1', $crawler->filter('#p' . $this->pst)->filter('.postlove_likers')->filter('span')->attr('title'));
	}
	
	public function test_guests_cannot_like()
	{
		$crw1 = self::request('GET', 'app.php/postlove/toggle/3', array(), array(), array('CONTENT_TYPE'	=> 'application/json'));
		
		$crawler = self::request('GET', "viewtopic.php?t={$this->tpic}");
		$this->assertContains('1', $crawler->filter('#p' . $this->pst)->filter('.postlove_likers')->filter('span')->attr('title'));
		
	}
	public function test_show_likes_given()
	{
		$this->login();
		$crawler = self::request('GET', "viewtopic.php?t=2&sid={$this->sid}");
		$this->assertEquals(0,  $crawler->filter('.post')->eq(0)->filter('.inner')->filter('.postprofile')->filter('.liked_info')->count());
		$this->assertEquals(0,  $crawler->filter('.post')->eq(0)->filter('.inner')->filter('.postprofile')->filter('.like_info')->count());
		$this->logout();
		
		$this->login();
		$this->admin_login();

		$this->add_lang_ext('aught13/postlove', 'info_acp_postlove');

		$crawler = self::request('GET', 'adm/index.php?i=-aught13-postlove-acp-acp_postlove_module&mode=main&sid=' . $this->sid);
		$form = $crawler->selectButton('submit')->form();
		$form->setValues(array(
			'poslove[postlove_show_likes]'	=> 1,
			'poslove[postlove_show_liked]'	=> 0,
		));
		$crawler = self::submit($form);
		$this->assertContains('Changes saved!', $crawler->text());
		$this->logout();
		$this->logout();

		$this->login();
		$crawler = self::request('GET', "viewtopic.php?t=1&sid={$this->sid}");
		$this->assertContains('x 1',  $crawler->filter('.post')->eq(0)->filter('.inner')->filter('.postprofile')->filter('.profile-custom-field')->filter('.liked_info')->parents()->text());
		//error above InvalidArgumentException: The current node list is empty.
		$this->assertEquals(0,  $crawler->filter('.post')->eq(0)->filter('.inner')->filter('.postprofile')->filter('.like_info')->count());
		$this->logout();
		
		$this->login();
		$this->admin_login();

		$this->add_lang_ext('aught13/postlove', 'info_acp_postlove');

		$crawler = self::request('GET', 'adm/index.php?i=-aught13-postlove-acp-acp_postlove_module&mode=main&sid=' . $this->sid);
		$form = $crawler->selectButton('submit')->form();
		$form->setValues(array(
			'poslove[postlove_show_likes]'	=> 0,
			'poslove[postlove_show_liked]'	=> 1,
		));
		$crawler = self::submit($form);
		$this->assertContains('Changes saved!', $crawler->text());
		$this->logout();
		$this->logout();
		
		$this->login();
		$crawler = self::request('GET', "viewtopic.php?t=2&sid={$this->sid}");
		$this->assertContains('x 1',  $crawler->filter('.post')->eq(0)->filter('.inner')->filter('.postprofile')->filter('.profile-custom-field')->filter('.like_info')->parents()->text());
		$this->assertEquals(0,  $crawler->filter('.post')->eq(0)->filter('.inner')->filter('.postprofile')->filter('.liked_info')->count());
		$this->logout();
		
		$this->login();
		$this->admin_login();

		$this->add_lang_ext('aught13/postlove', 'info_acp_postlove');

		$crawler = self::request('GET', 'adm/index.php?i=-aught13-postlove-acp-acp_postlove_module&mode=main&sid=' . $this->sid);
		$form = $crawler->selectButton('submit')->form();
		$form->setValues(array(
			'poslove[postlove_show_likes]'	=> 1,
			'poslove[postlove_show_liked]'	=> 1,
		));
		$crawler = self::submit($form);
		$this->assertContains('Changes saved!', $crawler->text());
		$this->logout();
		$this->logout();
		
		$this->login();
		$crawler = self::request('GET', "viewtopic.php?t=2&sid={$this->sid}");
		$this->assertContains('x 1',  $crawler->filter('.post')->eq(0)->filter('.inner')->filter('.postprofile')->filter('.profile-custom-field')->filter('.like_info')->parents()->text());
		$this->assertContains('x 1',  $crawler->filter('.post')->eq(0)->filter('.inner')->filter('.postprofile')->filter('.profile-custom-field')->filter('.liked_info')->parents()->text());
		$this->logout();
	}

	public function test_show_list()
	{
		$this->login();
		$this->add_lang_ext('aught13/postlove', 'postlove');
	
		$crawler = self::request('GET', "app.php/postlove/2?sid={$this->sid}");
		//$this->assertContains('zzazaza', $crawler->text());
		//$this->assertEquals(1, $crawler->filter('.inner')->filter('.topiclist')->filter('ul')->filter('li')->count());
	}
}
