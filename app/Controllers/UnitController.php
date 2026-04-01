<?php

class UnitController extends Controller
{
    public function index()
    {
        $this->requireLogin();
        $listData = UnitService::getUnitListData();
        $units = $listData['units'];
        $this->render('units/index', [
            'title' => 'Đơn vị tính',
            'units' => $units,
        ]);
    }

    public function store()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('unit');
        }

        $this->verifyCsrfToken();

        $result = UnitService::createUnit($_POST);
        if (!empty($result['message'])) {
            $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'unit');
    }

    public function update()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('unit');
        }

        $this->verifyCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        $result = UnitService::updateUnit($id, $_POST);
        if (!empty($result['message'])) {
            $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'unit');
    }

    public function delete()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $result = UnitService::deleteUnit($id);
        if (!empty($result['message'])) {
            $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'unit');
    }
}

