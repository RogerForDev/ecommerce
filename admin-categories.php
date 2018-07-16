<?php

use \Hcode\PageAdmin;
use \Hcode\Model\Category;

$app->get('/admin/categories', function(){
	
	$page = new PageAdmin();

	$categories = Category::listAll();
	
	$page->setTpl("categories", ["categories"=>$categories]);
});

$app->get('/admin/categories/create', function(){
	$page = new PageAdmin();

	$page->setTpl("categories-create");
});

$app->get('/admin/categories/:idcategory', function($idcategory){
	$page = new PageAdmin();

	$category = new Category();

	$category->get((int)$idcategory);

	$page->setTpl("categories-update", ["category"=>$category->getValues()]);
});

$app->post('/admin/categories/create', function(){

	$category = new Category();

	$category->setData($_POST);

	$category->save();

	header("Location: /admin/categories");
	exit;

});

$app->post('/admin/categories/:idcategory', function($idcategory){
	$page = new PageAdmin();

	$category = new Category();

	$category->get((int)$idcategory);

	$page->setTpl("categories-update", ["category"=>$category->getValues()]);
});

$app->get("/admin/categories/:idcategory/delete", function($idcategory){

	$category = new Category();

	$category->get((int)$idcategory);

	$category->delete();

	header("Location: /admin/categories");
	exit;

});


?>