<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Model\User;

class Cart extends Model {

    const SESSION = 'Cart';

    public static function getFromSession()
    {
        $cart = new Cart();

        if(isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0)
        {
            $cart->get((int)$_SESSION[Cart::SESSION]['idcart']);
        } else {
            $cart->getFromSessionID();

            if(!(int)$cart->getidcart() > 0)
            {
                $data = [
                    'dessessionid'=>session_id()
                ];

                if(User::checkLogin(false)){   

                    $user = User::getFromSession();

                    $data['iduser'] = $user->getiduser();

                }

                $cart->setData($data);

                $cart->save();



                $cart->setToSession();
               
            }
        }

        return $cart;

    } 
    public function setToSession()
    {
        $_SESSION[Cart::SESSION] = $this->getValues();
    }
    public function getFromSessionID(){
        
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE dessensionid = :dessensionid", array(
            ":dessensionid"=>session_id()
        ));
        if(count($results) > 0)
        {
            $this->setData($results[0]);
        }
    }

    public function get($idcart){
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart", array(
            ":idcart"=>$idcart
        ));
        if(count($results) > 0)
        {
            $this->setData($results[0]);
        }
    }

    public static function listAll(){
        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_cart ORDER BY descart");
    }

    public function save(){
        $sql = new Sql();        
        $results = $sql->select("CALL sp_carts_save(
            :idcart,
            :dessessionid,
            :iduser,
            :deszipcode,
            :vlfreight,
            :nrdays
        );", [
            ":idcart"=>$this->getidcart(),
            ":dessessionid"=>$this->getdessessionid(),
            ":iduser"=>$this->getiduser(),
            ":deszipcode"=>$this->getdeszipcode(),
            ":vlfreight"=>$this->getvlfreight(),
            ":nrdays"=>$this->getnrdays()
        ]);
        $this->setData($results[0]);
    }
    
    public function delete(){
        $sql = new Sql();
        $sql->query("DELETE FROM tb_cart WHERE idcart = :idcart", array(
            ":idcart"=>$this->getidcart()
        ));
        Cart::updateFile();
    }

    public static function updateFile(){
        $cart = Cart::listAll();

        $html = [];

        foreach($cart as $row){
            array_push($html, '<li><a href="/cart/'.$row['idcart'].'">'.$row['descart'].'</a></li>');
        }

        file_put_contents($_SERVER['DOCUMENT_ROOT']. DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "cart-menu.html", implode('', $html));
    }

    public function getProducts($relaled = true)
    {
        $sql = new Sql();
        if($relaled === true){
            return $sql->select("
            SELECT * FROM tb_products WHERE idproduct IN(
                SELECT a.idproduct
                FROM tb_products a 
                INNER JOIN tb_productscart b 
                USING(idproduct)
                WHERE b.idcart = :idcart
            );
            ",[
                ":idcart"=>$this->getidcart()
            ]);
        }else{
            return $sql->select("
            SELECT * FROM tb_products WHERE idproduct NOT IN(
                SELECT a.idproduct
                FROM tb_products a 
                INNER JOIN tb_productscart b 
                USING(idproduct)
                WHERE b.idcart = :idcart
            );
            ",[
                ":idcart"=>$this->getidcart()
            ]);
        }
    }

    public function getProductsPage($page = 1, $itensPerPage = 3)
    {
        $start = ($page - 1) * $itensPerPage;

        $sql = new Sql();

        $results = $sql->select("
            SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_products a
            INNER JOIN tb_productscart b ON a.idproduct = b.idproduct
            INNER JOIN tb_cart c ON c.idcart = b.idcart
            WHERE c.idcart = :idcart
            LIMIT $start, $itensPerPage;           
        ",[
            ':idcart'=>$this->getidcart()
        ]);

        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

        return [
            "data"=>Product::checkList($results),
            "total"=>(int)$resultTotal[0]["nrtotal"],
            "pages"=>ceil($resultTotal[0]["nrtotal"] / $itensPerPage)
        ];
    }

    public function addProduct(Product $product)
    {
        $sql = new Sql();

        $sql->query("INSERT INTO `tb_productscart`(`idcart`,`idproduct`) VALUES(:idcart,:idproduct)", [
            ":idcart"=>$this->getidcart(),
            ":idproduct"=>$product->getidproduct()
        ]);
    }
    public function removeProduct(Product $product)
    {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_productscart WHERE idcart = :idcart AND idproduct = :idproduct", [
            ":idcart"=>$this->getidcart(),
            ":idproduct"=>$product->getidproduct()
        ]);
    }

}

?>