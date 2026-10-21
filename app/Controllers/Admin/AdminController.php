<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use Closure;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * 管理画面（A-02〜A-10）の Controller の親
 *
 * 左メニューのバッジ、操作後の通知（flash）、絞り込み値の取り出しなど、全画面で同じ処理だけを置く。
 */
abstract class AdminController extends BaseController
{
    /**
     * 管理画面の View を描画する。レイアウトが使う共通の値（バッジ・通知・入力エラー）をここで足す
     *
     * @param array<string, mixed> $data
     */
    protected function render(string $view, array $data): string
    {
        return view($view, $data + [
            'badges' => service('stats')->badges(),
            'notice' => session()->getFlashdata('notice'),
            'errors' => session()->getFlashdata('errors') ?? [],
        ]);
    }

    /**
     * 操作後に一覧へ戻る。通知の文言はトースト（partials/admin_notice）に出す
     */
    protected function backWithNotice(string $notice): RedirectResponse
    {
        return redirect()->back()->with('notice', $notice);
    }

    /**
     * 入力エラーで戻る。入力値は保持する
     *
     * @param array<string, string> $errors
     */
    protected function backWithErrors(array $errors): RedirectResponse
    {
        $this->forgetSecrets();

        return redirect()->back()->withInput()->with('errors', $errors);
    }

    /**
     * クエリの絞り込み値。選択肢にない値は既定値に倒す
     *
     * @param array<int|string, string> $options 値 => 表示名
     */
    protected function choice(string $name, array $options, string $default): string
    {
        $value = (string) $this->request->getGet($name);

        return isset($options[$value]) ? $value : $default;
    }

    /**
     * 期間の絞り込み（?period= と、期間指定の ?from= ?to=（YYYY-MM-DD））。日付が入っていれば「期間指定」にする（監査 2026-10-21）
     *
     * @param array<string, string> $options 期間の選択肢（'custom' を含む）
     *
     * @return array{period: string, from: string, to: string}
     */
    protected function periodFilter(array $options): array
    {
        $date = fn (string $name): string => preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $this->text($name)) === 1 && strtotime($this->text($name)) !== false ? $this->text($name) : '';
        $from = $date('from');
        $to   = $date('to');

        if ($from !== '' && $to !== '' && $from > $to) {
            [$from, $to] = [$to, $from];
        }
        $period = $from !== '' || $to !== '' ? 'custom' : $this->choice('period', $options, 'all');

        return ['period' => $period, 'from' => $from, 'to' => $to];
    }

    /**
     * POST の選択値。選択肢にない値は既定値に倒す
     *
     * @param array<int|string, string> $options 値 => 表示名
     */
    protected function choicePost(string $name, array $options, string $default): string
    {
        $value = (string) $this->request->getPost($name);

        return isset($options[$value]) ? $value : $default;
    }

    /**
     * クエリの文字列（前後の空白を除く）
     */
    protected function text(string $name): string
    {
        return trim((string) $this->request->getGet($name));
    }

    /**
     * 絞り込みをクエリ文字列にする（空の項目は省く）。例：「?status=open&sort=pv」
     *
     * @param array<string, string> $filter
     */
    protected function query(array $filter): string
    {
        $query = http_build_query(array_filter($filter, static fn (string $v) => $v !== ''));

        return $query === '' ? '' : '?' . $query;
    }

    /**
     * ?page=（1以上）
     */
    protected function page(): int
    {
        return min(10000, max(1, (int) $this->request->getGet('page'))); // 巨大な値で offset が溢れないように上限
    }

    /**
     * 操作して元の画面へ戻る。対象がなければ 404、業務エラー（状態が合わない・重なった）は画面にエラーを出す
     *
     * @param callable(): void $action
     */
    protected function act(callable $action, string $notice): RedirectResponse
    {
        try {
            $this->orFail($action);
        } catch (AppException $e) {
            return $this->backWithErrors(['_' => $e->getMessage()]);
        }

        return $this->backWithNotice($notice);
    }

    /**
     * 対象がなければ 404（操作用。戻り値を使わない）
     *
     * @param callable(): void $action
     */
    protected function orFail(callable $action): void
    {
        try {
            $action();
        } catch (NotFoundException) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    /**
     * 対象がなければ 404（取得用）
     *
     * @template T of object
     *
     * @param Closure(): T $find
     *
     * @return T
     */
    protected function findOrFail(Closure $find): object
    {
        try {
            return $find();
        } catch (NotFoundException) {
            throw PageNotFoundException::forPageNotFound();
        }
    }
}
