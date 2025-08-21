<?php
/**
 * Plugin Name: Gomoku Game
 * Description: 五目並べゲームをWordPressサイトに組み込むプラグインです。15×15のボードで遊べます。
 * Version: 1.3.2
 * Author: KantanPro
 * Author URI: https://www.kantanpro.com/kantanpro-page
 * License: GPL v2 or later
 * Text Domain: gomoku
 * Requires at least: 5.0
 * Tested up to: 6.9.1
 * Requires PHP: 7.4
 */

// セキュリティ対策
if (!defined('ABSPATH')) {
    exit;
}

// デバッグモードの設定（本番環境では無効にする）
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// プラグインの定数定義
define('GOMOKU_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GOMOKU_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GOMOKU_VERSION', '1.3.1');
define('GOMOKU_GITHUB_USERNAME', 'kantanpro');
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
        add_action('init', array($this, 'load_textdomain'));
        add_action('admin_init', array($this, 'init_updater'));
        add_action('admin_init', array($this, 'refresh_plugin_cache'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * プラグインの初期化
     */
    public function init() {
        try {
            // ショートコードの登録
            if (file_exists(GOMOKU_PLUGIN_PATH . 'includes/class-gomoku-shortcode.php')) {
                require_once GOMOKU_PLUGIN_PATH . 'includes/class-gomoku-shortcode.php';
                new Gomoku_Shortcode();
            } else {
                error_log('Gomoku Plugin: class-gomoku-shortcode.php が見つかりません');
            }
            
            // ゲームロジッククラスの読み込み
            if (file_exists(GOMOKU_PLUGIN_PATH . 'includes/class-gomoku-game.php')) {
                require_once GOMOKU_PLUGIN_PATH . 'includes/class-gomoku-game.php';
            } else {
                error_log('Gomoku Plugin: class-gomoku-game.php が見つかりません');
            }
            
            // AJAX処理の登録
            add_action('wp_ajax_gomoku_force_update_check', array($this, 'force_update_check'));
            add_action('wp_ajax_gomoku_toggle_auto_update', array($this, 'toggle_auto_update'));
            
            // プラグイン情報の詳細表示を有効化
            add_filter('plugin_row_meta', array($this, 'add_plugin_row_meta'), 10, 2);
            add_filter('plugins_api', array($this, 'plugins_api_handler'), 10, 3);
            
            // プラグインの自動更新状態を制御
            add_filter('auto_update_plugin', array($this, 'control_auto_update'), 10, 2);
            
            // プラグインリストの自動更新ボタンをカスタマイズ
            add_filter('plugin_auto_update_setting_html', array($this, 'customize_auto_update_button'), 10, 2);
        } catch (Exception $e) {
            error_log('Gomoku Plugin 初期化エラー: ' . $e->getMessage());
        }
    }
    
    /**
     * テキストドメインの読み込み
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'gomoku',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
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
     * 管理画面のスクリプトとスタイルを読み込み
     */
    public function admin_enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('gomoku-admin', GOMOKU_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), GOMOKU_VERSION, true);
        wp_enqueue_style('gomoku-admin', GOMOKU_PLUGIN_URL . 'admin/css/admin.css', array(), GOMOKU_VERSION);
        
        // プラグインリストページでのみ自動更新ボタンのJavaScriptを読み込み
        $screen = get_current_screen();
        if ($screen && $screen->id === 'plugins') {
            wp_enqueue_script('gomoku-auto-update', GOMOKU_PLUGIN_URL . 'admin/js/auto-update.js', array('jquery'), GOMOKU_VERSION, true);
            wp_localize_script('gomoku-auto-update', 'gomoku_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('gomoku_toggle_auto_update')
            ));
        }
    }
    
    /**
     * プラグインの有効化時の処理
     */
    public function activate() {
        try {
            // データベーステーブルの作成
            global $wpdb;
            
            $table_name = $wpdb->prefix . 'gomoku_scores';
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                winner varchar(10) NOT NULL,
                board_size int(11) NOT NULL,
                moves_count int(11) NOT NULL,
                game_date datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY game_date (game_date)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            // デフォルト設定の追加
            add_option('gomoku_board_size', 15);
            add_option('gomoku_enable_scores', 1);
            add_option('gomoku_max_history', 10);
            add_option('gomoku_ai_difficulty', 'medium');
            add_option('gomoku_dark_theme', 'auto');
            add_option('gomoku_character_mode', 'stones');
            add_option('gomoku_github_token', '');
            add_option('gomoku_auto_update', true);
            
            flush_rewrite_rules();
        } catch (Exception $e) {
            error_log('Gomoku Plugin 有効化エラー: ' . $e->getMessage());
            // エラーが発生してもプラグインは有効化される
        }
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
    public function init_updater() {
        // 管理画面でのみアップデートチェッカーを有効化
        if (is_admin()) {
            try {
                if (file_exists(GOMOKU_PLUGIN_PATH . 'includes/class-plugin-updater.php')) {
                    require_once GOMOKU_PLUGIN_PATH . 'includes/class-plugin-updater.php';
                    
                    // GitHub Access Token（オプション）
                    $github_token = get_option('gomoku_github_token', '');
                    
                    $this->updater = new Gomoku_Plugin_Updater(
                        __FILE__,
                        GOMOKU_GITHUB_USERNAME,
                        GOMOKU_GITHUB_REPOSITORY,
                        $github_token
                    );
                } else {
                    error_log('Gomoku Plugin: class-plugin-updater.php が見つかりません');
                }
            } catch (Exception $e) {
                error_log('Gomoku Plugin アップデーター初期化エラー: ' . $e->getMessage());
            }
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
    
    /**
     * プラグインキャッシュをリフレッシュ
     */
    public function refresh_plugin_cache() {
        // プラグイン情報のキャッシュをクリア
        if (function_exists('wp_clean_plugins_cache')) {
            wp_clean_plugins_cache();
        }
        
        // プラグインの詳細情報を再読み込み
        if (function_exists('get_plugins')) {
            get_plugins();
        }
    }
    
    /**
     * プラグイン行のメタ情報を追加
     */
    public function add_plugin_row_meta($links, $file) {
        if (plugin_basename(__FILE__) === $file) {
            $new_links = array(
                sprintf(
                    '<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s">%s</a>',
                    esc_url(admin_url('plugin-install.php?tab=plugin-information&plugin=gomoku&TB_iframe=true&width=600&height=550')),
                    esc_attr(sprintf('%s の詳細を表示', 'Gomoku Game')),
                    esc_html__('詳細を表示', 'gomoku')
                )
            );
            return array_merge($links, $new_links);
        }
        return $links;
    }
    
    /**
     * plugins_api フィルターでプラグイン詳細モーダル用情報を返す
     */
    public function plugins_api_handler($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }
        
        $requested_slug = isset($args->slug) ? $args->slug : (isset($args->plugin) ? $args->plugin : '');
        if (!$requested_slug || $requested_slug !== 'gomoku') {
            return $result;
        }
        
        // プラグイン情報を構築
        $info = new stdClass();
        $info->name = 'Gomoku Game';
        $info->slug = 'gomoku';
        $info->version = GOMOKU_VERSION;
        $info->author = 'KantanPro';
        $info->author_profile = 'https://www.kantanpro.com/kantanpro-page';
        $info->homepage = 'https://www.kantanpro.com/kantanpro-page';
        $info->requires = '5.0';
        $info->tested = '6.9.1';
        $info->requires_php = '7.4';
        $info->last_updated = '2024-12-20';
        
        $info->sections = array(
            'description' => '五目並べゲームをWordPressサイトに組み込むプラグインです。15×15のボードで遊べます。AI対戦機能、ダークテーマ対応、キャラクターモード、GitHub連携による自動更新機能を搭載しています。',
            'installation' => '1. プラグインファイルを `/wp-content/plugins/gomoku/` ディレクトリにアップロードします
2. WordPressの管理画面でプラグインを有効化します
3. ページや投稿に `[gomoku]` ショートコードを挿入します
4. 必要に応じて管理画面の「Gomoku Game」設定でカスタマイズします
5. 自動更新の有効・無効を設定できます',
            'changelog' => '= 1.3.1 =
* WordPress 6.7.0対応：翻訳読み込みタイミングの修正
* プラグインアップデートチェッカーの初期化タイミングを調整
* ヘッダー送信エラーの修正

= 1.3.0 =
* AI対戦モードを常時ONに変更
* AIレベル選択機能を追加（初級・中級・上級）
* GitHub連携による自動更新機能を追加
* キャラクターモード機能の追加

= 1.2.0 =
* ダークテーマ対応の追加
* システムのダークモード設定を自動検出

= 1.1.0 =
* AI対戦機能の追加
* ゲーム履歴機能の追加

= 1.0.0 =
* ファーストテイク
* 五目並べゲームの基本機能を実装',
            'screenshots' => '1. ゲーム画面 - 15×15のボードで五目並べをプレイ
2. 管理画面 - ゲーム設定と統計情報の表示
3. モバイル表示 - レスポンシブデザインでの表示例'
        );
        
        $info->banners = array(
            'high' => '',
            'low' => ''
        );
        
        $info->icons = array(
            'default' => ''
        );
        
        return $info;
    }
    
    /**
     * プラグインの自動更新状態を制御
     */
    public function control_auto_update($update, $item) {
        // Gomokuプラグインの場合のみ制御
        if ($item->plugin === plugin_basename(__FILE__)) {
            // 設定で自動更新が無効になっている場合はfalseを返す
            if (get_option('gomoku_auto_update', true) == false) {
                return false;
            }
        }
        
        return $update;
    }
    
    /**
     * 自動更新状態を切り替えるAJAX処理
     */
    public function toggle_auto_update() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'gomoku_toggle_auto_update')) {
            wp_send_json_error('セキュリティチェックに失敗しました');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        // 現在の自動更新設定を取得して反転
        $current_status = get_option('gomoku_auto_update', true);
        $new_status = !$current_status;
        
        // 設定を更新
        update_option('gomoku_auto_update', $new_status);
        
        // プラグインキャッシュをクリア
        $this->refresh_plugin_cache();
        
        wp_send_json_success(array(
            'new_status' => $new_status,
            'message' => $new_status ? '自動更新が有効になりました' : '自動更新が無効になりました'
        ));
    }
    
    /**
     * プラグインリストの自動更新ボタンをカスタマイズ
     */
    public function customize_auto_update_button($html, $plugin_file) {
        // Gomokuプラグインの場合のみカスタマイズ
        if ($plugin_file === plugin_basename(__FILE__)) {
            $auto_update_enabled = get_option('gomoku_auto_update', true);
            $nonce = wp_create_nonce('gomoku_toggle_auto_update');
            
            if ($auto_update_enabled) {
                $html = sprintf(
                    '<button type="button" class="button-link gomoku-toggle-auto-update" data-status="enabled" data-nonce="%s">自動更新を有効化</button>',
                    esc_attr($nonce)
                );
            } else {
                $html = sprintf(
                    '<button type="button" class="button button-primary gomoku-toggle-auto-update" data-status="disabled" data-nonce="%s">自動更新無効</button>',
                    esc_attr($nonce)
                );
            }
        }
        
        return $html;
    }
}

// プラグインの初期化
new Gomoku_Plugin();
