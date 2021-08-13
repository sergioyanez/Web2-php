<?php

require_once 'models/item.model.php';
require_once 'views/item.view.php';
require_once 'helpers/auth.helper.php';
require_once 'views/error.view.php';
require_once 'models/img.model.php';


class ItemController
{

    private $model;
    private $view;
    private $viewError;
    private $modelprod;
    private $modelImg;

    public function __construct()
    {
        $this->model = new ItemModel();
        $this->view = new ItemView();
        $this->modelprod = new ProductModel();
        $this->viewError = new ErrorView();
        $this->modelImg = new ImagenModel();
    }

    public function showItems()
    {
        $rubros = $this->model->getItems();
        $this->view->items($rubros);  // actualizo la vista
    }

    public function insertItem()
    {

        if (AuthHelper::checkLogged()) { //Barrera para usuario logueado

            $nombre = $_POST['nombreItem']; // toma los valores enviados por el usuario

            $imagenitem = $_FILES['imagenrubro']["name"];
            $ubimagenrubro = $_FILES['imagenrubro']["tmp_name"];
            $nombrefinal = "images/imagesRubros/" . uniqid("", true) . "."
                . strtolower(pathinfo($imagenitem, PATHINFO_EXTENSION));
            //var_dump($nombrefinal);           
            move_uploaded_file($ubimagenrubro, $nombrefinal);
            var_dump($nombrefinal);
           
            $item = $this->model->getItemNombre($nombre); //verifica si el rubro está repetido
            if (!empty($item)) {
                $this->view->ErrorItemRepetido();
            }
            if (empty($nombre) && empty($imagenitem)) {
                $this->view->ErrorAlCargarItem();   //verifica si el campo está vacío
            }
            if (empty($item) && !empty($nombre) && !empty($imagenitem)) {
                // inserta en la DB y redirige
                $success = $this->model->insertOneItem($nombre, $nombrefinal);    //inserta a la base de datos el rubro
                if ($success) {
                    header('Location: ' . BASE_URL . "listrubros");
                }
            }
        }
    }

    public function formItem()
    {
        if (AuthHelper::checkLogged()) { // si está logueado muestra el formulario de alta item
            $this->view->ShowFormByItem();
        }
    }

    public function deleteItem($rubro)
    {
        if (AuthHelper::checkLogged()) {
            $productos = $this->modelprod->getProductsByItem($rubro);
            if (empty($productos)) {
                $this->model->borrarItem($rubro);
                header('Location: ' . BASE_URL . "listrubros");    //En BD Eliminación 
            } else
                $this->view->errorAlBorrarRubro();
        }
    }

    public function editItem($idItem)
    {
        if (AuthHelper::checkLogged()) {
            $item = $this->model->getItem($idItem);   //toma el item desde la BD
            if (!empty($item)) {
                $this->view->showFormEdit($item);       //Muestra el formulario para editar con los datos precargados
            } else
                $this->viewError->showError("Rubro Inexistente");
        }
    }

    public function itemEditado()
    {
        $nombre = $_POST['nombreItem'];
        $id = $_POST['iditem'];   //Se encuentra oculto
        $imagenitem = $_FILES['imagenrubro']["name"];
        if (!empty($imagenitem)) {
            $ubimagenrubro = $_FILES['imagenrubro']["tmp_name"];
            $nombrefinal = "images/imagesRubros/" . uniqid("", true) . "."
                . strtolower(pathinfo($imagenitem, PATHINFO_EXTENSION));
            move_uploaded_file($ubimagenrubro, $nombrefinal);
        }
        if (!empty($nombrefinal)) {
            $this->model->modifyItem($id, $nombre, $nombrefinal);
        } else {
            $this->model->modifyItem($id, $nombre);
        }
        header('Location: ' . BASE_URL . "listrubros");
    }

    public function addImages($idrubro)
    {
        $imagenitem = $_FILES['image']["name"];
        if (!empty($imagenitem)) {
            $ubimagenrubro = $_FILES['image']["tmp_name"];
            $nombrefinal = "images/imagesRubros/" . uniqid("", true) . "."
                . strtolower(pathinfo($imagenitem, PATHINFO_EXTENSION));
            move_uploaded_file($ubimagenrubro, $nombrefinal);
        }
        $this->modelImg->agregar($idrubro, $nombrefinal);

        header('Location: ' . BASE_URL . "productos_por_rubros/$idrubro");
    }

    public function deleteImagen($idrubro, $id)
    {
        if (AuthHelper::checkLogged()) {
            $this->modelImg->deleteImage($id);
            $ruta = $this->modelImg->obtenerRutaImagen($id);
            unlink($ruta->path);
            header('Location: ' . BASE_URL . "productos_por_rubros/$idrubro");
        }
    }
}
