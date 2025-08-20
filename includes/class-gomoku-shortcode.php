<?php
/**
 * 五目並べゲームのショートコードクラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class Gomoku_Shortcode {
    
    public function __construct() {
        add_shortcode('gomoku', array($this, 'render_game'));
        add_action('wp_ajax_gomoku_save_score', array($this, 'save_score'));
        add_action('wp_ajax_nopriv_gomoku_save_score', array($this, 'save_score'));
    }
    
    /**
     * ショートコードの表示
     */
    public function render_game($atts) {
        $atts = shortcode_atts(array(
            'board_size' => 15,
            'theme' => 'default',
            'ai_mode' => 'false'
        ), $atts);
        
        ob_start();
        ?>
        <div id="gomoku-game" class="gomoku-game" data-board-size="<?php echo esc_attr($atts['board_size']); ?>" data-theme="<?php echo esc_attr($atts['theme']); ?>" data-ai-mode="<?php echo esc_attr($atts['ai_mode']); ?>">
            <div class="gomoku-header">
                <h3 class="gomoku-title">五目並べゲーム</h3>
                <div class="gomoku-status">
                    <span class="current-player">現在のプレイヤー: <span id="current-player">黒</span></span>
                    <span class="game-status" id="game-status">ゲーム中</span>
                </div>
            </div>
            
            <div class="gomoku-board-container">
                <div class="gomoku-board" id="gomoku-board">
                    <?php $this->render_board($atts['board_size']); ?>
                </div>
            </div>
            
            <div class="gomoku-controls">
                <button type="button" id="reset-game" class="gomoku-btn gomoku-btn-reset">ゲームリセット</button>
                <button type="button" id="undo-move" class="gomoku-btn gomoku-btn-undo">一手戻す</button>
                <button type="button" id="toggle-ai" class="gomoku-btn gomoku-btn-ai">AI対戦: OFF</button>
            </div>
            
            <div class="gomoku-info">
                <div class="gomoku-scores">
                    <div class="score-item">
                        <span class="player-label">黒: </span>
                        <span class="score-value" id="score-black">0</span>
                    </div>
                    <div class="score-item">
                        <span class="player-label">白: </span>
                        <span class="score-value" id="score-white">0</span>
                    </div>
                </div>
                <div class="gomoku-history">
                    <h4>ゲーム履歴</h4>
                    <div id="game-history" class="history-list"></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * ゲームボードのHTML生成
     */
    private function render_board($size) {
        for ($row = 0; $row < $size; $row++) {
            echo '<div class="gomoku-row" data-row="' . $row . '">';
            for ($col = 0; $col < $size; $col++) {
                echo '<div class="gomoku-cell" data-row="' . $row . '" data-col="' . $col . '"></div>';
            }
            echo '</div>';
        }
    }
    
    /**
     * スコアの保存（AJAX）
     */
    public function save_score() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'gomoku_nonce')) {
            wp_die('セキュリティチェックに失敗しました');
        }
        
        $winner = sanitize_text_field($_POST['winner']);
        $moves = intval($_POST['moves']);
        
        // データベースにスコアを保存（オプション）
        $scores = get_option('gomoku_scores', array());
        $scores[] = array(
            'winner' => $winner,
            'moves' => $moves,
            'date' => current_time('mysql')
        );
        
        // 最新の10件のみ保持
        if (count($scores) > 10) {
            $scores = array_slice($scores, -10);
        }
        
        update_option('gomoku_scores', $scores);
        
        wp_send_json_success(array('message' => 'スコアが保存されました'));
    }
}
