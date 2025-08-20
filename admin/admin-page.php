<?php
/**
 * 管理画面の設定ページ
 */

if (!defined('ABSPATH')) {
    exit;
}

// 設定の保存処理
if (isset($_POST['submit']) && wp_verify_nonce($_POST['gomoku_settings_nonce'], 'gomoku_settings')) {
    $board_size = intval($_POST['board_size']);
    $enable_scores = isset($_POST['enable_scores']) ? 1 : 0;
    $max_history = intval($_POST['max_history']);
    
    update_option('gomoku_board_size', $board_size);
    update_option('gomoku_enable_scores', $enable_scores);
    update_option('gomoku_max_history', $max_history);
    
    echo '<div class="notice notice-success"><p>設定が保存されました。</p></div>';
}

// 現在の設定値を取得
$board_size = get_option('gomoku_board_size', 15);
$enable_scores = get_option('gomoku_enable_scores', 1);
$max_history = get_option('gomoku_max_history', 10);

// 統計情報の取得
$scores = get_option('gomoku_scores', array());
$total_games = count($scores);
$black_wins = 0;
$white_wins = 0;

foreach ($scores as $score) {
    if ($score['winner'] === '黒') {
        $black_wins++;
    } elseif ($score['winner'] === '白') {
        $white_wins++;
    }
}
?>

<div class="wrap">
    <h1>Gomoku Game 設定</h1>
    
    <div class="gomoku-admin-container">
        <!-- 設定フォーム -->
        <div class="gomoku-admin-section">
            <h2>基本設定</h2>
            <form method="post" action="">
                <?php wp_nonce_field('gomoku_settings', 'gomoku_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="board_size">ボードサイズ</label>
                        </th>
                        <td>
                            <select name="board_size" id="board_size">
                                <option value="15" <?php selected($board_size, 15); ?>>15 × 15</option>
                                <option value="19" <?php selected($board_size, 19); ?>>19 × 19</option>
                                <option value="13" <?php selected($board_size, 13); ?>>13 × 13</option>
                            </select>
                            <p class="description">ゲームボードのサイズを選択してください。</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="enable_scores">スコア記録</label>
                        </th>
                        <td>
                            <input type="checkbox" name="enable_scores" id="enable_scores" value="1" <?php checked($enable_scores, 1); ?>>
                            <label for="enable_scores">ゲーム結果を記録する</label>
                            <p class="description">チェックを入れると、ゲーム結果がデータベースに保存されます。</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="max_history">履歴保存数</label>
                        </th>
                        <td>
                            <input type="number" name="max_history" id="max_history" value="<?php echo esc_attr($max_history); ?>" min="5" max="50" class="regular-text">
                            <p class="description">保存するゲーム履歴の最大数を設定してください（5-50）。</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="ai_difficulty">AI難易度</label>
                        </th>
                        <td>
                            <select name="ai_difficulty" id="ai_difficulty">
                                <option value="easy">初級</option>
                                <option value="medium" selected>中級</option>
                                <option value="hard">上級</option>
                            </select>
                            <p class="description">AI対戦の難易度を設定してください。</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="設定を保存">
                </p>
            </form>
        </div>
        
        <!-- 統計情報 -->
        <div class="gomoku-admin-section">
            <h2>統計情報</h2>
            <div class="gomoku-stats">
                <div class="stat-item">
                    <span class="stat-label">総ゲーム数</span>
                    <span class="stat-value"><?php echo $total_games; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">黒の勝利</span>
                    <span class="stat-value"><?php echo $black_wins; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">白の勝利</span>
                    <span class="stat-value"><?php echo $white_wins; ?></span>
                </div>
                <?php if ($total_games > 0): ?>
                <div class="stat-item">
                    <span class="stat-label">黒の勝率</span>
                    <span class="stat-value"><?php echo round(($black_wins / $total_games) * 100, 1); ?>%</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">白の勝率</span>
                    <span class="stat-value"><?php echo round(($white_wins / $total_games) * 100, 1); ?>%</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 使用方法 -->
        <div class="gomoku-admin-section">
            <h2>使用方法</h2>
            <div class="gomoku-usage">
                <h3>ショートコード</h3>
                <p>以下のショートコードを使用して、ページや投稿にゲームを表示できます：</p>
                <code>[gomoku]</code>
                
                <h3>パラメータ</h3>
                <ul>
                    <li><code>board_size</code>: ボードサイズ（例: <code>[gomoku board_size="19"]</code>）</li>
                    <li><code>theme</code>: テーマ（例: <code>[gomoku theme="dark"]</code>）</li>
                </ul>
                
                <h3>例</h3>
                <ul>
                    <li><code>[gomoku]</code> - デフォルト設定（15×15）</li>
                    <li><code>[gomoku board_size="19"]</code> - 19×19のボード</li>
                    <li><code>[gomoku board_size="13" theme="compact"]</code> - 13×13のコンパクトテーマ</li>
                </ul>
            </div>
        </div>
        
        <!-- 履歴データ -->
        <?php if (!empty($scores)): ?>
        <div class="gomoku-admin-section">
            <h2>最近のゲーム履歴</h2>
            <div class="gomoku-history-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>日時</th>
                            <th>勝利者</th>
                            <th>手数</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($scores, -10) as $score): ?>
                        <tr>
                            <td><?php echo esc_html($score['date']); ?></td>
                            <td><?php echo esc_html($score['winner']); ?></td>
                            <td><?php echo esc_html($score['moves']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.gomoku-admin-container {
    max-width: 1200px;
}

.gomoku-admin-section {
    background: white;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.gomoku-admin-section h2 {
    margin-top: 0;
    color: #23282d;
    border-bottom: 2px solid #667eea;
    padding-bottom: 10px;
}

.gomoku-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.stat-item {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    border-left: 4px solid #667eea;
}

.stat-label {
    display: block;
    font-size: 14px;
    color: #666;
    margin-bottom: 8px;
}

.stat-value {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #667eea;
}

.gomoku-usage h3 {
    color: #23282d;
    margin-top: 20px;
    margin-bottom: 10px;
}

.gomoku-usage code {
    background: #f1f1f1;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}

.gomoku-usage ul {
    margin-left: 20px;
}

.gomoku-history-table {
    margin-top: 20px;
}

.gomoku-history-table table {
    width: 100%;
}

.gomoku-history-table th {
    font-weight: 600;
    background: #f8f9fa;
}
</style>
