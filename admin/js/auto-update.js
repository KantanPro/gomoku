jQuery(document).ready(function($) {
    // 自動更新ボタンのクリックイベントを処理
    $(document).on('click', '.gomoku-toggle-auto-update', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var currentStatus = button.data('status');
        var nonce = button.data('nonce');
        
        // ボタンを無効化してローディング状態に
        button.prop('disabled', true).text('処理中...');
        
        // AJAXで自動更新状態を切り替え
        $.ajax({
            url: gomoku_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gomoku_toggle_auto_update',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // 成功時の処理
                    var newStatus = response.data.new_status;
                    
                    if (newStatus) {
                        // 有効化された場合
                        button.data('status', 'enabled')
                               .text('自動更新を有効化')
                               .removeClass('button-primary')
                               .addClass('button-link');
                    } else {
                        // 無効化された場合
                        button.data('status', 'disabled')
                               .text('自動更新無効')
                               .removeClass('button-link')
                               .addClass('button-primary');
                    }
                    
                    // 成功メッセージを表示
                    if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
                        // WordPress 5.0以降の通知システムを使用
                        wp.data.dispatch('core/notices').createSuccessNotice(response.data.message, {
                            id: 'gomoku-auto-update-notice'
                        });
                    } else {
                        // 従来の通知方法
                        alert(response.data.message);
                    }
                    
                    // プラグインリストを更新
                    location.reload();
                } else {
                    // エラー時の処理
                    var errorMessage = response.data || 'エラーが発生しました';
                    if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
                        wp.data.dispatch('core/notices').createErrorNotice(errorMessage, {
                            id: 'gomoku-auto-update-error'
                        });
                    } else {
                        alert('エラー: ' + errorMessage);
                    }
                }
            },
            error: function() {
                // 通信エラー時の処理
                var errorMessage = '通信エラーが発生しました';
                if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
                    wp.data.dispatch('core/notices').createErrorNotice(errorMessage, {
                        id: 'gomoku-auto-update-error'
                    });
                } else {
                    alert('エラー: ' + errorMessage);
                }
            },
            complete: function() {
                // ボタンを元の状態に戻す
                button.prop('disabled', false);
            }
        });
    });
});
