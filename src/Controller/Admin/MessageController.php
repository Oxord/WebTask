<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repository\ContactMessageRepository;

class MessageController extends AdminController
{
    private ContactMessageRepository $messages;

    public function __construct()
    {
        parent::__construct();
        $this->messages = new ContactMessageRepository();
    }

    public function index(Request $request): Response
    {
        $page = $this->pageParam($request);
        $unreadOnly = (string) $request->query('unread', '') === '1';

        $result = $this->messages->paginate($page, 20, $unreadOnly ? true : null);

        return $this->render('messages/index', [
            'items' => $result['items'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'page' => $page,
            'unreadOnly' => $unreadOnly,
            'unreadCount' => $this->messages->unreadCount(),
        ]);
    }

    public function markRead(Request $request, string $id): Response
    {
        $messageId = $this->intId($id);
        $this->messages->markRead($messageId);
        Session::flash('success', 'Сообщение отмечено как прочитанное.');

        return $this->redirect('/admin/messages');
    }

    public function destroy(Request $request, string $id): Response
    {
        $messageId = $this->intId($id);
        $this->messages->delete($messageId);
        Session::flash('success', 'Сообщение удалено.');

        return $this->redirect('/admin/messages');
    }
}
