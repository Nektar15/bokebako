<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Exceptions\ValidationException;
use App\Services\SettingService;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * A-10 運用設定
 */
final class SettingController extends AdminController
{
    /**
     * A-10
     */
    public function index(): string
    {
        $settings = service('settings');
        $display  = service('display');
        $values   = [];

        // 入力エラーで戻ったときは入力値を優先する（チェックボックスは送信されなければ OFF）
        $resubmitted = old('odai_max') !== null;

        foreach ($settings->all() as $key => $value) {
            $values[$key] = match (true) {
                is_bool($value)  => $resubmitted ? old($key) === '1' : $value,
                is_array($value) => $resubmitted ? (array) old($key) : $value,
                default          => (string) old($key, $value),
            };
        }

        return $this->render('admin/a10_settings', [
            'screenId'      => 'A-10',
            'title'         => '運用設定',
            'values'        => $values,
            'defaultLabels' => $this->defaultLabels(),
            'limitMethods'  => SettingService::LIMIT_METHODS,
            'aiTargets'     => SettingService::AI_TARGETS,
            'aiSelected'    => array_fill_keys((array) $values['ai_targets'], true),
            'aiUsed'        => $display->number($settings->aiMonthlyUsed()),
            'history'       => $display->logs($settings->history()),
        ]);
    }

    /**
     * 「初期値 …」の表示（View で計算しないため、ここで文字列にする）
     *
     * @return array<string, string>
     */
    private function defaultLabels(): array
    {
        $d = service('settings')->defaults();

        return [
            'odai_max'         => $d['odai_max'] . '文字',
            'answer_max'       => $d['answer_max'] . '文字',
            'open_hours'       => $d['open_hours'] . '時間',
            'select_hours'     => $d['select_hours'] . '時間',
            'burst'            => $d['burst_count'] . '回／' . $d['burst_window_sec'] . '秒',
            'limit_method'     => SettingService::LIMIT_METHODS[$d['limit_method']],
            'ban_minutes'      => $d['ban_minutes'] . '分',
            'report'           => $d['report_count'] . '件／' . $d['report_window_min'] . '分',
            'surge_count'      => $d['surge_count'] . '件',
            'ai_enabled'       => $d['ai_enabled'] ? 'ON' : 'OFF',
            'ai_targets'       => count((array) $d['ai_targets']) . 'つすべて',
            'ai_monthly_limit' => service('display')->number((int) $d['ai_monthly_limit']) . '件',
            'ai_auto_hide'     => $d['ai_auto_hide'] ? 'ON' : 'OFF',
            'tpl'              => '設定済みの文',
            'hashtag'          => (string) $d['hashtag'],
        ];
    }

    /**
     * A-10 保存
     */
    public function update(): RedirectResponse
    {
        try {
            service('settings')->update((array) $this->request->getPost());
        } catch (ValidationException $e) {
            return $this->backWithErrors($e->errors);
        }

        return redirect()->to(admin_url('settings'))->with('notice', '設定を保存しました。次のアクセスから反映されます');
    }
}
