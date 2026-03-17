<?php

class UnitController extends Controller
{
    public function index()
    {
        $this->requireLogin();
        $units = Unit::all();
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

        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        if ($name !== '') {
            Unit::create([
                'name' => $name,
            ]);
            $this->setFlash('success', 'Đã thêm đơn vị tính.');
        }

        $this->redirect('unit');
    }

    public function update()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('unit');
        }

        $this->verifyCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';

        if ($id && $name !== '') {
            Unit::update($id, [
                'name' => $name,
            ]);
            $this->setFlash('success', 'Đã cập nhật đơn vị tính.');
        }

        $this->redirect('unit');
    }

    public function delete()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id) {
            Unit::delete($id);
            $this->setFlash('success', 'Đã xóa đơn vị tính.');
        }

        $this->redirect('unit');
    }
}

