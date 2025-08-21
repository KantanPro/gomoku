<?php
/**
 * Plugin Name: Gomoku Game
 * Plugin URI: https://github.com/yourusername/gomoku-wordpress-plugin
 * Description: 五目並べゲームをWordPressサイトに組み込むプラグインです。15×15のボードで遊べます。
 * Version: 1.3.0
 * Author: Your Name
 * Author URI: https://github.com/yourusername
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gomoku
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * GitHub Plugin URI: yourusername/gomoku-wordpress-plugin
 */

// セキュリティ対策
if (!defined('ABSPATH')) {
    exit;
}

// プラグインの定数定義
define('GOMOKU_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GOMOKU_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GOMOKU_VERSION', '1.3.0');
define('GOMOKU_GITHUB_USERNAME', 'yourusername');
define('GOMOKU_GITHUB_REPOSITORY', 'gomoku-wordpress-plugin');

/**
 * プラグインの初期化クラス
 */
class Gomoku_Plugin {
    
    private $updater;
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // プラグインアップデートチェッカーを初期化
        $this->init_updater();
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
        
        // AJAX処理の登録
        add_action('wp_ajax_gomoku_force_update_check', array($this, 'force_update_check'));
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
    
    /**
     * プラグインアップデートチェッカーの初期化
     */
    private function init_updater() {
        // 管理画面でのみアップデートチェッカーを有効化
        if (is_admin()) {
            require_once GOMOKU_PLUGIN_PATH . 'includes/class-plugin-updater.php';
            
            // GitHub Access Token（オプション）
            $github_token = get_option('gomoku_github_token', '');
            
            $this->updater = new Gomoku_Plugin_Updater(
                __FILE__,
                GOMOKU_GITHUB_USERNAME,
                GOMOKU_GITHUB_REPOSITORY,
                $github_token
            );
        }
    }
    
    /**
     * 強制更新チェックのAJAX処理
     */
    public function force_update_check() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'gomoku_force_update')) {
            wp_send_json_error('セキュリティチェックに失敗しました');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        // 更新チェックを強制実行
        if (isset($this->updater)) {
            $this->updater->force_check();
            wp_send_json_success('更新チェックが完了しました');
        } else {
            wp_send_json_error('アップデートチェッカーが初期化されていません');
        }
    }
}

// プラグインの初期化
new Gomoku_Plugin();
