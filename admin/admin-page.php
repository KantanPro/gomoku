<?php
/**
 * 管理画面の設定ページ
 */

if (!defined('ABSPATH')) {
    exit;
}

// エラーハンドリングの設定
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 設定の保存処理
if (isset($_POST['submit']) && wp_verify_nonce($_POST['gomoku_settings_nonce'], 'gomoku_settings')) {
    try {
        $board_size = intval($_POST['board_size']);
        $enable_scores = isset($_POST['enable_scores']) ? 1 : 0;
        $max_history = intval($_POST['max_history']);
        $ai_difficulty = sanitize_text_field($_POST['ai_difficulty']);
        $dark_theme = sanitize_text_field($_POST['dark_theme']);
        $character_mode = sanitize_text_field($_POST['character_mode']);
        $github_token = sanitize_text_field($_POST['github_token']);
        $auto_update = isset($_POST['auto_update']) ? 1 : 0;
        
        update_option('gomoku_board_size', $board_size);
        update_option('gomoku_enable_scores', $enable_scores);
        update_option('gomoku_max_history', $max_history);
        update_option('gomoku_ai_difficulty', $ai_difficulty);
        update_option('gomoku_dark_theme', $dark_theme);
        update_option('gomoku_character_mode', $character_mode);
        update_option('gomoku_github_token', $github_token);
        update_option('gomoku_auto_update', $auto_update);
        
        echo '<div class="notice notice-success"><p>設定が保存されました。</p></div>';
        
        // 設定保存後にページをリロードして最新の値を表示
        echo '<script>setTimeout(function(){ location.reload(); }, 1000);</script>';
    } catch (Exception $e) {
        error_log('Gomoku Admin 設定保存エラー: ' . $e->getMessage());
        echo '<div class="notice notice-error"><p>設定の保存中にエラーが発生しました。エラーログを確認してください。</p></div>';
    }
}

// 現在の設定値を取得
$board_size = get_option('gomoku_board_size', 15);
$enable_scores = get_option('gomoku_enable_scores', 1);
$max_history = get_option('gomoku_max_history', 10);
$ai_difficulty = get_option('gomoku_ai_difficulty', 'medium');
$dark_theme = get_option('gomoku_dark_theme', 'auto');
$character_mode = get_option('gomoku_character_mode', 'stones');
$github_token = get_option('gomoku_github_token', '');
$auto_update = get_option('gomoku_auto_update', true);

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
                                <option value="easy" <?php selected($ai_difficulty, 'easy'); ?>>初級</option>
                                <option value="medium" <?php selected($ai_difficulty, 'medium'); ?>>中級</option>
                                <option value="hard" <?php selected($ai_difficulty, 'hard'); ?>>上級</option>
                            </select>
                            <p class="description">AI対戦の難易度を設定してください。</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="dark_theme">ダークテーマ</label>
                        </th>
                        <td>
                            <select name="dark_theme" id="dark_theme">
                                <option value="auto" <?php selected($dark_theme, 'auto'); ?>>自動検出</option>
                                <option value="light" <?php selected($dark_theme, 'light'); ?>>ライトテーマ</option>
                                <option value="dark" <?php selected($dark_theme, 'dark'); ?>>ダークテーマ</option>
                            </select>
                            <p class="description">テーマの設定方法を選択してください。</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="character_mode">キャラクターモード</label>
                        </th>
                        <td>
                            <select name="character_mode" id="character_mode">
                                <option value="stones" <?php selected($character_mode, 'stones'); ?>>石</option>
                                <option value="character" <?php selected($character_mode, 'character'); ?>>😎🤡 キャラクター</option>
                                <option value="fantasy" <?php selected($character_mode, 'fantasy'); ?>>👺💀 ファンタジー</option>
                                <option value="anime" <?php selected($character_mode, 'anime'); ?>>👽☠️ アニメ</option>
                                <option value="emoji" <?php selected($character_mode, 'emoji'); ?>>😼🫥 絵文字</option>
                                <option value="demon" <?php selected($character_mode, 'demon'); ?>>😈👻 悪魔vsおばけ</option>
                            </select>
                            <p class="description">ゲームで使用するキャラクターの種類を選択してください。</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="github_token">GitHub Access Token</label>
                        </th>
                        <td>
                            <input type="password" name="github_token" id="github_token" value="<?php echo esc_attr($github_token); ?>" class="regular-text">
                            <p class="description">GitHubの更新チェック用アクセストークン（オプション）。プライベートリポジトリの場合に必要です。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">自動更新</th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_update" id="auto_update" value="1" <?php checked(get_option('gomoku_auto_update', true), true); ?> />
                                プラグインの自動更新を有効にする
                            </label>
                            <p class="description">チェックを外すと、手動更新のみになります</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="設定を保存">
                </p>
            </form>
        </div>
        
        <!-- 自動更新状態 -->
        <div class="gomoku-admin-section">
            <h2>自動更新状態</h2>
            <div class="gomoku-auto-update-status">
                <?php if ($auto_update): ?>
                    <div class="status-enabled">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <strong>自動更新が有効になっています</strong>
                        <p>GitHubリリースの更新通知が表示され、プラグインリストで「自動更新を有効化」が表示されます</p>
                    </div>
                <?php else: ?>
                    <div class="status-disabled">
                        <span class="dashicons dashicons-no-alt"></span>
                        <strong>自動更新が無効になっています</strong>
                        <p>手動更新のみになり、プラグインリストで「自動更新無効」が表示されます</p>
                    </div>
                <?php endif; ?>
            </div>
                    <p class="description">
            <strong>注意:</strong> この設定を変更した後、プラグインリストの表示を更新するには、ページを再読み込みしてください。
        </p>
        <p class="description">
            <strong>ヒント:</strong> プラグインリストの「自動更新」列でも直接切り替えができます。
        </p>
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
                    <li><code>ai_level</code>: AIレベル（例: <code>[gomoku ai_level="hard"]</code>）</li>
                    <li><code>dark_theme</code>: ダークテーマ（例: <code>[gomoku dark_theme="dark"]</code>）</li>
                    <li><code>character_mode</code>: キャラクターモード（例: <code>[gomoku character_mode="emoji"]</code>）</li>
                </ul>
                
                <h3>例</h3>
                <ul>
                    <li><code>[gomoku]</code> - 管理画面の設定を使用</li>
                    <li><code>[gomoku board_size="19"]</code> - 19×19のボード</li>
                    <li><code>[gomoku ai_level="easy"]</code> - 初級AIで対戦</li>
                    <li><code>[gomoku dark_theme="dark"]</code> - 強制ダークテーマ</li>
                    <li><code>[gomoku character_mode="fantasy"]</code> - ファンタジーキャラクター</li>
                    <li><code>[gomoku character_mode="emoji"]</code> - 絵文字キャラクター</li>
                    <li><code>[gomoku character_mode="demon"]</code> - 悪魔vsおばけ</li>
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

.gomoku-auto-update-status {
    margin-top: 20px;
}

.gomoku-auto-update-status .status-enabled,
.gomoku-auto-update-status .status-disabled {
    display: flex;
    align-items: center;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.gomoku-auto-update-status .status-enabled {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.gomoku-auto-update-status .status-disabled {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.gomoku-auto-update-status .dashicons {
    font-size: 24px;
    margin-right: 15px;
}

.gomoku-auto-update-status .status-enabled .dashicons {
    color: #28a745;
}

.gomoku-auto-update-status .status-disabled .dashicons {
    color: #dc3545;
}

.gomoku-auto-update-status strong {
    font-size: 16px;
    margin-bottom: 5px;
}

.gomoku-auto-update-status p {
    margin: 0;
    font-size: 14px;
    opacity: 0.8;
}


</style>

<script>
jQuery(document).ready(function($) {
    // 自動更新設定の変更を監視
    $('#auto_update').on('change', function() {
        var isEnabled = $(this).is(':checked');
        var statusDiv = $('.gomoku-auto-update-status');
        
        if (isEnabled) {
            statusDiv.html(`
                <div class="status-enabled">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <strong>自動更新が有効になっています</strong>
                    <p>GitHubリリースの更新通知が表示され、プラグインリストで「自動更新を有効化」が表示されます</p>
                </div>
            `);
        } else {
            statusDiv.html(`
                <div class="status-disabled">
                    <span class="dashicons dashicons-no-alt"></span>
                    <strong>自動更新が無効になっています</strong>
                    <p>手動更新のみになり、プラグインリストで「自動更新無効」が表示されます</p>
                </div>
            `);
        }
    });
});
</script>