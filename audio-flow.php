<?php
/**
 * Plugin Name: Audio Flow
 * Plugin URI: https://github.com/elizarov-n906/audio-flow
 * Description: Продвинутый аудио-плеер с панелью управления в админке и шорткодами.
 * Version: 2.0.9
 * Author: Elizarov_n906
 * Author URI: https://github.com/elizarov-n906
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: audio-flow
 */

if (!defined('ABSPATH')) exit;

// =============================================================================
// 1. МЕНЮ В "КОНСОЛЬ"
// =============================================================================
add_action('admin_menu', 'af_add_admin_menu');

function af_add_admin_menu() {
    add_submenu_page(
        'index.php', 
        'Настройки Audio Flow', 
        'Audio Flow', 
        'manage_options', 
        'audio-flow-settings', 
        'af_render_settings_page'
    );
}

// =============================================================================
// 2. ССЫЛКА "НАСТРОЙКИ" В СПИСКЕ ПЛАГИНОВ
// =============================================================================
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'af_action_links');

function af_action_links($links) {
    $settings_link = '<a href="' . admin_url('index.php?page=audio-flow-settings') . '" style="font-weight: bold;">Настройки</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// =============================================================================
// 3. СТРАНИЦА НАСТРОЕК И ЛОГИКА
// =============================================================================
function af_render_settings_page() {
    $message = '';
    $tracks = get_option('af_tracks_list', array());
    if (!is_array($tracks)) $tracks = array();

    if (isset($_POST['af_action']) && check_admin_referer('af_nonce')) {
        if (isset($_POST['af_delete_id'])) {
            $delete_id = intval($_POST['af_delete_id']);
            if ($delete_id > 0 && isset($tracks[$delete_id])) {
                unset($tracks[$delete_id]); 
                update_option('af_tracks_list', $tracks);
                $message = '<div class="notice notice-success is-dismissible"><p>Трек успешно удален.</p></div>';
            }
        } elseif (isset($_POST['af_add'])) {
            $new_title = sanitize_text_field($_POST['af_new_title']);
            $new_src = esc_url_raw($_POST['af_new_src']);
            if (!empty($new_title) && !empty($new_src)) {
                $new_id = empty($tracks) ? 1 : max(array_keys($tracks)) + 1;
                $tracks[$new_id] = array('title' => $new_title, 'src' => $new_src);
                update_option('af_tracks_list', $tracks);
                $message = '<div class="notice notice-success is-dismissible"><p>Трек добавлен с ID: ' . $new_id . '</p></div>';
            }
        } elseif (isset($_POST['af_save'])) {
            $clean_tracks = array();
            if (isset($_POST['af_ids'])) {
                foreach ($_POST['af_ids'] as $old_id) {
                    $old_id = intval($old_id);
                    if ($old_id <= 0 || $old_id === 'new') continue;
                    $title = isset($_POST['af_title_' . $old_id]) ? sanitize_text_field($_POST['af_title_' . $old_id]) : '';
                    $src = isset($_POST['af_src_' . $old_id]) ? esc_url_raw($_POST['af_src_' . $old_id]) : '';
                    if (!empty($title) && !empty($src)) {
                        $clean_tracks[$old_id] = array('title' => $title, 'src' => $src);
                    }
                }
            }
            update_option('af_tracks_list', $clean_tracks);
            $message = '<div class="notice notice-success is-dismissible"><p>Изменения сохранены.</p></div>';
        }
    }
    
    $tracks = get_option('af_tracks_list', array());
    if (!is_array($tracks)) $tracks = array();
    ksort($tracks); 
    ?>
    <div class="wrap elizarov_n906">
        <h1>Управление аудио Audio Flow</h1>
        <?php echo $message; ?>
        <form method="post" action="">
            <?php wp_nonce_field('af_nonce'); ?>
            <input type="hidden" name="af_action" value="1">
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 10%;">ID</th>
                        <th style="width: 25%;">Название</th>
                        <th style="width: 35%;">Ссылка на файл</th>
                        <th style="width: 20%;">Шорткод</th>
                        <th style="width: 10%;">Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tracks)) : foreach ($tracks as $id => $track) : ?>
                    <tr>
                        <td style="font-weight: bold; text-align: center;"><?php echo intval($id); ?></td>
                        <input type="hidden" name="af_ids[]" value="<?php echo intval($id); ?>">
                        <td><input type="text" name="af_title_<?php echo intval($id); ?>" value="<?php echo esc_attr($track['title']); ?>" class="regular-text"></td>
                        <td><input type="text" name="af_src_<?php echo intval($id); ?>" value="<?php echo esc_attr($track['src']); ?>" class="large-text"></td>
                        <td><code>[audio_flow id="<?php echo intval($id); ?>"]</code></td>
                        <td>
                            <button type="submit" name="af_delete_id" value="<?php echo intval($id); ?>" class="button button-small button-link-delete" onclick="return confirm('Удалить?');">Удалить</button>
                        </td>
                    </tr>
                    <?php endforeach; else : ?>
                    <tr><td colspan="5" style="text-align:center; padding: 20px;">Нет треков.</td></tr>
                    <?php endif; ?>
                    <tr style="background-color: #f0f6fc;">
                        <td>Новый</td>
                        <td><input type="text" name="af_new_title" placeholder="Название" class="regular-text"></td>
                        <td><input type="text" name="af_new_src" placeholder="https://..." class="large-text"></td>
                        <td>—</td>
                        <td><button type="submit" name="af_add" class="button button-primary">Добавить</button></td>
                    </tr>
                </tbody>
            </table>
            <br>
            <?php submit_button('Сохранить изменения', 'primary', 'af_save'); ?>
        </form>
        <hr style="margin-top: 40px;">
        <p style="color: #666; font-size: 13px; border-top: 1px solid #ccc; padding-top: 15px;">
            Автор плагина: <strong>elizarov_n906</strong>
        </p>
    </div>
    <?php
}

// =============================================================================
// 4. ВЫВОД CSS И JS (ТОЛЬКО 1 РАЗ НА СТРАНИЦУ)
// =============================================================================
add_action('wp_footer', 'af_print_assets');

function af_print_assets() {
    static $printed = false;
    if ($printed) return;
    $printed = true;
    ?>
    <style>
        .af-player {
            display: flex; flex-direction: column; gap: 15px;
            padding: 20px 24px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 100%; margin: 30px auto;
        }
        .af-header { display: flex; align-items: center; gap: 20px; }
        .af-btn {
            width: 48px; height: 48px; border-radius: 50%; background: #000000;
            border: none; cursor: pointer; display: flex; align-items: center;
            justify-content: center; transition: background 0.3s, transform 0.2s; flex-shrink: 0;
        }
        .af-btn:hover { background: #333333; transform: scale(1.05); }
        .af-icon { position: relative; width: 14px; height: 16px; margin-left: 2px; }
        .af-icon::before {
            content: ''; position: absolute; top: 0; left: 0; width: 0; height: 0;
            border-top: 8px solid transparent; border-bottom: 8px solid transparent;
            border-left: 14px solid #ffffff; transition: opacity 0.2s, transform 0.2s;
        }
        .af-icon::after {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(to right, #fff 0%, #fff 25%, transparent 25%, transparent 75%, #fff 75%, #fff 100%);
            opacity: 0; transform: scale(0.5); transition: opacity 0.2s, transform 0.2s;
        }
        .is-playing .af-icon::before { opacity: 0; transform: scale(0.5); }
        .is-playing .af-icon::after { opacity: 1; transform: scale(1); }
        .is-playing .af-icon { margin-left: 0; }
        .af-info { flex: 1; display: flex; flex-direction: column; gap: 4px; }
        .af-title { font-size: 16px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.3; }
        .af-duration { font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 6px; }
        .af-duration-label { font-weight: 500; }
        .af-time { font-weight: 600; color: #000000; font-variant-numeric: tabular-nums; }
        .af-vol-wrap { display: flex; align-items: center; gap: 8px; width: 130px; flex-shrink: 0; }
     input[type="range"].af-vol {
    -webkit-appearance: none;
    width: 100%;
    height: 4px;
    background: #cbd5e1;
    border-radius: 2px;
    outline: none;
    cursor: pointer;
    padding: 0;
    color: inherit;
    border: none !important;
}
        .af-vol::-webkit-slider-thumb {
            -webkit-appearance: none; width: 12px; height: 12px;
            border-radius: 50%; background: #000000; cursor: pointer;
        }
        .af-vol::-moz-range-thumb {
            width: 12px; height: 12px; border-radius: 50%; background: #000000; cursor: pointer; border: none;
        }
        .af-footer { display: flex; align-items: center; position: relative; }
        .af-progress-wrap {
            flex: 1; height: 6px; background: #e2e8f0; border-radius: 3px;
            overflow: visible; cursor: pointer; position: relative;
        }
        .af-progress { height: 100%; width: 0%; background: #000000; border-radius: 3px; transition: width 0.1s linear; }
        .af-tooltip {
            position: absolute; bottom: 100%; left: 0; transform: translateX(-50%);
            background: #000000; color: #ffffff; padding: 4px 8px; border-radius: 4px;
            font-size: 12px; font-weight: 600; white-space: nowrap; pointer-events: none;
            opacity: 0; transition: opacity 0.2s; margin-bottom: 8px; font-variant-numeric: tabular-nums;
        }
        .af-tooltip::after {
            content: ''; position: absolute; top: 100%; left: 50%; transform: translateX(-50%);
            border: 4px solid transparent; border-top-color: #000000;
        }
        .af-progress-wrap:hover .af-tooltip { opacity: 1; }
        @media (max-width: 480px) {
            .af-player { padding: 16px; gap: 12px; }
            .af-header { flex-wrap: wrap; gap: 12px; }
            .af-vol-wrap { width: auto; max-width: 160px; order: 3; margin-top: 4px; padding: 8px 12px; background: rgba(0, 0, 0, 0.04); border-radius: 20px; }
            .af-vol { width: 90px; }
        }
    </style>

    <script>
    (function() {
        const formatTime = (seconds) => {
            if (isNaN(seconds) || !isFinite(seconds)) return "00:00";
            const m = Math.floor(seconds / 60);
            const s = Math.floor(seconds % 60);
            return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        };

        const initPlayers = () => {
            const players = document.querySelectorAll('.af-player');
            players.forEach(p => {
                const btn = p.querySelector('.af-btn');
                const aud = p.querySelector('.af-audio');
                const prog = p.querySelector('.af-progress');
                const pWrap = p.querySelector('.af-progress-wrap');
                const vol = p.querySelector('.af-vol');
                const timeDisplay = p.querySelector('.af-time');
                const tooltip = p.querySelector('.af-tooltip');

                if (!aud) return;

                aud.addEventListener('loadedmetadata', () => {
                    timeDisplay.textContent = formatTime(aud.duration);
                });
                if (aud.readyState >= 1) {
                    timeDisplay.textContent = formatTime(aud.duration);
                }

                btn.addEventListener('click', () => {
                    if (aud.paused) {
                        document.querySelectorAll('.af-audio').forEach(otherAud => {
                            if (otherAud !== aud) { 
                                otherAud.pause(); 
                                const otherPlayer = otherAud.closest('.af-player');
                                if (otherPlayer) otherPlayer.classList.remove('is-playing');
                            }
                        });
                        aud.play().catch(e => console.error('Audio play error:', e));
                        p.classList.add('is-playing');
                    } else {
                        aud.pause();
                        p.classList.remove('is-playing');
                    }
                });

                aud.addEventListener('timeupdate', () => {
                    if (aud.duration) prog.style.width = (aud.currentTime / aud.duration) * 100 + '%';
                });

                pWrap.addEventListener('mousemove', (e) => {
                    if (!aud.duration) return;
                    const rect = pWrap.getBoundingClientRect();
                    const offsetX = e.clientX - rect.left;
                    const percent = Math.max(0, Math.min(1, offsetX / rect.width));
                    const hoverTime = percent * aud.duration;
                    
                    tooltip.textContent = formatTime(hoverTime);
                    tooltip.style.left = (percent * 100) + '%';
                });

                pWrap.addEventListener('click', (e) => {
                    const rect = pWrap.getBoundingClientRect();
                    aud.currentTime = ((e.clientX - rect.left) / rect.width) * aud.duration;
                });

                vol.addEventListener('input', (e) => aud.volume = e.target.value);
                
                aud.addEventListener('ended', () => {
                    p.classList.remove('is-playing');
                    prog.style.width = '0%';
                });
            });
        };

        // Запускаем сразу, так как скрипт в футере (DOM уже готов)
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initPlayers);
        } else {
            initPlayers();
        }
    })();
    </script>
    <?php
}

// =============================================================================
// 5. ШОРТКОД [audio_flow id="..."] (ТЕПЕРЬ ВЫВОДИТ ТОЛЬКО HTML)
// =============================================================================
add_shortcode('audio_flow', 'af_render_player');

function af_render_player($atts) {
    $atts = shortcode_atts(array('id' => ''), $atts, 'audio_flow');
    
    $id = intval($atts['id']);
    if ($id < 1) return '<!-- Audio Flow: Неверный ID -->';

    $tracks = get_option('af_tracks_list', array());
    if (!is_array($tracks) || !isset($tracks[$id])) return '<!-- Audio Flow: Трек не найден -->';

    $track = $tracks[$id];
    
    // Генерируем только HTML, стили и скрипты подтянутся через wp_footer
    ob_start();
    ?>
    <div class="af-player">
        <div class="af-header">
            <button class="af-btn" aria-label="Play"><span class="af-icon"></span></button>
            <div class="af-info">
                <div class="af-title"><?php echo esc_html($track['title']); ?></div>
                <div class="af-duration">
                    <span class="af-duration-label">Длительность:</span>
                    <span class="af-time">00:00</span>
                </div>
            </div>
            <div class="af-vol-wrap">
                <input type="range" class="af-vol" min="0" max="1" step="0.01" value="1" title="Громкость">
            </div>
        </div>
        
        <div class="af-footer">
            <div class="af-progress-wrap">
                <div class="af-progress"></div>
                <div class="af-tooltip">00:00</div>
            </div>
        </div>

        <audio class="af-audio" src="<?php echo esc_url($track['src']); ?>" preload="metadata"></audio>
    </div>
    <?php
    return ob_get_clean();
}