<?php
/**
 * Plugin Name: Gomoku Game
 * Plugin URI: https://example.com/gomoku
 * Description: 五目並べゲームをWordPressサイトに組み込むプラグインです。15×15のボードで遊べます。
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: gomoku
 */

// セキュリティ対策
if (!defined('ABSPATH')) {
    exit;
}

// プラグインの定数定義
define('GOMOKU_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GOMOKU_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GOMOKU_VERSION', '1.0.0');

/**
 * プラグインの初期化クラス
 */
class Gomoku_Plugin {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * プラグインの初期化
     */
    public function init() {
        // ショートコードの登録
        require_once GOMOKU_PLUGIN_PATH . 'includes/class-gomoku-shortcode.php';
        new Gomoku_Shortcode();
        
        // ゲームロジッククラスの読み込み
        require_once GOMOKU_PLUGIN_PATH . 'includes/class-gomoku-game.php';
    }
    
    /**
     * スクリプトとスタイルの読み込み
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'gomoku-game',
            GOMOKU_PLUGIN_URL . 'public/js/gomoku.js',
            array('jquery'),
            GOMOKU_VERSION,
            true
        );
        
        wp_enqueue_style(
            'gomoku-style',
            GOMOKU_PLUGIN_URL . 'public/css/gomoku.css',
            array(),
            GOMOKU_VERSION
        );
        
        // AJAX用のnonceとURLをJavaScriptに渡す
        wp_localize_script('gomoku-game', 'gomoku_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gomoku_nonce'),
            'board_size' => 15
        ));
    }
    
    /**
     * 管理メニューの追加
     */
    public function add_admin_menu() {
        add_options_page(
            'Gomoku Game Settings',
            'Gomoku Game',
            'manage_options',
            'gomoku-settings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * 管理画面の表示
     */
    public function admin_page() {
        include GOMOKU_PLUGIN_PATH . 'admin/admin-page.php';
    }
    
    /**
     * プラグインの有効化時の処理
     */
    public function activate() {
        // データベーステーブルの作成など
        flush_rewrite_rules();
    }
    
    /**
     * プラグインの無効化時の処理
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
}

// プラグインの初期化
new Gomoku_Plugin();
