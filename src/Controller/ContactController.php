<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Repository\ContactMessageRepository;

class ContactController
{
    public function index(Request $request): Response
    {
        $errors = Session::get('_errors', []);
        Session::remove('_errors');

        $html = View::render('pages/contacts/index', [
            'pageTitle' => 'Контакты',
            'errors' => $errors,
        ]);

        return Response::html($html);
    }

    public function store(Request $request): Response
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'name' => 'required|min:2|max:150',
            'email' => 'required|email|max:190',
            'phone' => 'phone',
            'message' => 'required|min:10|max:2000',
        ]);

        if ($validator->fails()) {
            Session::flashInput($data);
            Session::set('_errors', $validator->errors());
            Session::flash('error', 'Не получилось отправить сообщение — проверьте форму.');

            return Response::redirect(url('/contacts'));
        }

        (new ContactMessageRepository())->create($validator->validated());
        Session::flash('success', 'Спасибо! Мы получили ваше сообщение и ответим в ближайшее время.');

        return Response::redirect(url('/contacts'));
    }
}
