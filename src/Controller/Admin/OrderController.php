<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\PromotionRepository;
use App\Service\CartService;
use App\Service\OrderService;

class OrderController extends AdminController
{
    private OrderRepository $orders;
    private OrderService $orderService;

    public function __construct()
    {
        parent::__construct();
        $this->orders = new OrderRepository();
        // OrderService требует CartService в конструкторе, хотя updateStatus/allowedStatuses
        // его не используют — собираем зависимость по контракту сервиса.
        $this->orderService = new OrderService(
            $this->orders,
            new CartService(new ProductRepository(), new PromotionRepository()),
            new ProductRepository()
        );
    }

    public function index(Request $request): Response
    {
        $page = $this->pageParam($request);
        $status = (string) $request->query('status', '');
        $allowed = $this->orderService->allowedStatuses();
        $status = in_array($status, $allowed, true) ? $status : '';

        $result = $this->orders->paginate($page, 20, $status !== '' ? $status : null);

        return $this->render('orders/index', [
            'items' => $result['items'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'page' => $page,
            'status' => $status,
            'statuses' => $allowed,
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $order = $this->orders->findWithItems($this->intId($id));
        if ($order === null) {
            throw new NotFoundException('Заказ не найден.');
        }

        return $this->render('orders/show', [
            'order' => $order,
            'statuses' => $this->orderService->allowedStatuses(),
        ]);
    }

    public function updateStatus(Request $request, string $id): Response
    {
        $orderId = $this->intId($id);
        $order = $this->orders->find($orderId);
        if ($order === null) {
            throw new NotFoundException('Заказ не найден.');
        }

        try {
            $this->orderService->updateStatus($orderId, (string) $request->input('status', ''));
            Session::flash('success', 'Статус заказа №' . $order['order_number'] . ' обновлён.');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ($messages as $message) {
                    Session::flash('error', $message);
                }
            }
        }

        return $this->redirect('/admin/orders/' . $orderId);
    }
}
