<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Exceptions\ValidationException;
use App\Services\ContactService;
use CodeIgniter\HTTP\RedirectResponse;

/** U-18 お問い合わせ */
final class ContactController extends BaseController
{
    public function index(): string
    {
        return view('front/u18_contact', [
            'types'            => ContactService::TYPES,
            'urlRequiredTypes' => ContactService::URL_REQUIRED_TYPES,
            'errors'           => session()->getFlashdata('errors') ?? [],
            'old'              => [
                'type'  => (string) (old('type') ?? 'general'),
                'email' => (string) old('email'),
                'url'   => (string) old('url'),
                'body'  => (string) old('body'),
            ],
        ]);
    }

    public function send(): RedirectResponse
    {
        $input = [
            'type'  => (string) $this->request->getPost('type'),
            'email' => (string) $this->request->getPost('email'),
            'url'   => (string) $this->request->getPost('url'),
            'body'  => (string) $this->request->getPost('body'),
        ];

        try {
            service('contact')->send($input);
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->with('errors', $e->errors);
        }

        return redirect()->to('/contact/done');
    }

    public function done(): string
    {
        return view('front/u18_contact_done');
    }
}
