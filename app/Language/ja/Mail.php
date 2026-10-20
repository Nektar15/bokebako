<?php

declare(strict_types=1);

/**
 * 管理者への通知メールの文面（FEATURES 0-8）。MailService::notifyAdmin() が使う
 *
 * {name} は差し込む値。本文の最後には footer が付く。文面を変えるときはここだけ直す。
 */
return [
    'subjectPrefix' => '[ぼけばこ] ',
    'footer'        => "\n\n――\nこのメールはぼけばこから自動で送っています。返信しても届きません。\n管理画面：{adminUrl}",

    'report' => [
        'subject' => '通報を受け付けました（{reason}）',
        'body'    => "{target} {id} が通報されました（理由：{reason}）。\n管理画面の「通報・モデレーション」で確認してください。\n{url}",
    ],
    'autoHidden' => [
        'subject' => '通報により {count} 件を一時非表示にしました',
        'body'    => "通報が条件に達したため、{count} 件を一時非表示にしました。\n管理画面の「通報・モデレーション」で確認し、問題がなければ再公開してください。\n{url}",
    ],
    'contact' => [
        'subject' => 'お問い合わせ（{type}）',
        'body'    => "お問い合わせ #{id}（{type}）を受け付けました。\n管理画面の「お問い合わせ管理」で確認してください。\n{url}",
    ],
    'ngReview' => [
        'subject' => 'NGワード「要確認」のお題が開催されました',
        'body'    => "お題 #{id} が NG ワード「要確認」に当たりました（開催は通しています）。\n検知した内容：{detail}\n管理画面の「通報・モデレーション」で確認してください。\n{url}",
    ],
];
