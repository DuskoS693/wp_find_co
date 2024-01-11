<?php
/*
Plugin Name: Voting Find Co
Description: Voting plugin for Find.Co
Version: 0.1
Author: Dusko Stanisic
*/

class Voting {
	public function __construct(){
		add_action('wp_enqueue_scripts', array($this, 'enqueue_plugins_scripts'));
	}
}

new Voting();