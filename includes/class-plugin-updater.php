<?php
/**
 * GitHub Plugin Updater
 * WordPressプラグインのGitHubリリース対応アップデートチェッククラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class Gomoku_Plugin_Updater {
    
    private $plugin_slug;
    private $version;
    private $plugin_path;
    private $plugin_file;
    private $github_username;
    private $github_repository;
    private $github_access_token;
    private $requires_wp_version;
    private $tested_wp_version;
    
    public function __construct($plugin_file, $github_username, $github_repository, $github_access_token = '') {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->plugin_path = dirname($plugin_file);
        $this->github_username = $github_username;
        $this->github_repository = $github_repository;
        $this->github_access_token = $github_access_token;
        
        // プラグイン情報を取得
        $plugin_data = get_plugin_data($plugin_file);
        $this->version = $plugin_data['Version'];
        $this->requires_wp_version = !empty($plugin_data['RequiresWP']) ? $plugin_data['RequiresWP'] : '5.0';
        $this->tested_wp_version = !empty($plugin_data['TestedUpTo']) ? $plugin_data['TestedUpTo'] : get_bloginfo('version');
        
        // WordPressフックに登録（admin_init以降に実行）
        add_action('admin_init', array($this, 'init_hooks'));
    }
    
    /**
     * フックの初期化
     */
    public function init_hooks() {
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_plugin_update'));
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
    }
    
    /**
     * GitHubからリリース情報を取得
     */
    private function get_remote_version() {
        $url = "https://api.github.com/repos/{$this->github_username}/{$this->github_repository}/releases/latest";
        
        $args = array(
            'timeout' => 15,
            'headers' => array(
                'User-Agent' => 'WordPress-Plugin-Updater',
            ),
        );
        
        // GitHub Access Tokenが設定されている場合
        if (!empty($this->github_access_token)) {
            $args['headers']['Authorization'] = 'token ' . $this->github_access_token;
        }
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data['tag_name'])) {
            return false;
        }
        
        return $data;
    }
    
    /**
     * プラグインの更新チェック
     */
    public function check_for_plugin_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // 現在のバージョンがチェック済みバージョンに含まれているか確認
        if (!isset($transient->checked[$this->plugin_slug])) {
            return $transient;
        }
        
        // リモートバージョンを取得
        $remote_version_data = $this->get_remote_version();
        
        if ($remote_version_data === false) {
            return $transient;
        }
        
        $remote_version = ltrim($remote_version_data['tag_name'], 'v');
        
        // バージョン比較
        if (version_compare($this->version, $remote_version, '<')) {
            $plugin_data = array(
                'slug' => dirname($this->plugin_slug),
                'new_version' => $remote_version,
                'url' => $remote_version_data['html_url'],
                'package' => $remote_version_data['zipball_url'],
                'icons' => array(),
                'banners' => array(),
                'banners_rtl' => array(),
                'tested' => $this->tested_wp_version,
                'requires_php' => '7.4',
                'compatibility' => new stdClass(),
            );
            
            $transient->response[$this->plugin_slug] = (object) $plugin_data;
        }
        
        return $transient;
    }
    
    /**
     * プラグイン情報ポップアップ
     */
    public function plugin_popup($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }
        
        if (!isset($args->slug) || $args->slug !== dirname($this->plugin_slug)) {
            return $result;
        }
        
        $remote_version_data = $this->get_remote_version();
        
        if ($remote_version_data === false) {
            return $result;
        }
        
        $plugin_data = get_plugin_data($this->plugin_file);
        
        $result = new stdClass();
        $result->name = $plugin_data['Name'];
        $result->slug = dirname($this->plugin_slug);
        $result->version = ltrim($remote_version_data['tag_name'], 'v');
        $result->author = $plugin_data['Author'];
        $result->author_profile = $plugin_data['AuthorURI'];
        $result->last_updated = $remote_version_data['published_at'];
        $result->homepage = $plugin_data['PluginURI'];
        $result->short_description = $plugin_data['Description'];
        $result->sections = array(
            'description' => $plugin_data['Description'],
            'changelog' => $this->get_changelog($remote_version_data),
        );
        $result->download_link = $remote_version_data['zipball_url'];
        $result->trunk = $remote_version_data['zipball_url'];
        $result->requires = $this->requires_wp_version;
        $result->tested = $this->tested_wp_version;
        $result->requires_php = '7.4';
        $result->rating = 0;
        $result->ratings = array();
        $result->num_ratings = 0;
        $result->support_threads = 0;
        $result->support_threads_resolved = 0;
        $result->active_installs = 0;
        $result->downloaded = 0;
        $result->last_updated = $remote_version_data['published_at'];
        $result->added = $remote_version_data['created_at'];
        $result->tags = array();
        $result->compatibility = new stdClass();
        
        return $result;
    }
    
    /**
     * インストール後の処理
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;
        
        $install_directory = plugin_dir_path($this->plugin_file);
        $wp_filesystem->move($result['destination'], $install_directory);
        $result['destination'] = $install_directory;
        
        if ($this->plugin_slug) {
            activate_plugin($this->plugin_slug);
        }
        
        return $result;
    }
    
    /**
     * 変更履歴を取得
     */
    private function get_changelog($release_data) {
        $changelog = '<h4>Version ' . ltrim($release_data['tag_name'], 'v') . '</h4>';
        $changelog .= '<p><strong>リリース日:</strong> ' . date('Y年m月d日', strtotime($release_data['published_at'])) . '</p>';
        
        if (!empty($release_data['body'])) {
            $changelog .= '<div>' . wpautop($release_data['body']) . '</div>';
        }
        
        return $changelog;
    }
    
    /**
     * 手動で更新チェックを実行
     */
    public function force_check() {
        delete_site_transient('update_plugins');
        wp_update_plugins();
    }
}
