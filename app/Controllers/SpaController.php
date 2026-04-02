<?php

class SpaController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $this->render('spa/index', [
            'title' => 'Vue Admin (Beta)',
        ]);
    }
}
