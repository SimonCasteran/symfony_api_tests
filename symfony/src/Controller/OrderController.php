<?php
namespace App\Controller;
Use App\Entity\Product;
Use App\Entity\Cart;
Use App\Entity\Order;
use App\Repository\ProductRepository;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use App\Tools\JWT;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OrderController extends AbstractController{
	
	/**
	* @var ProductRepository
    * @var CartRepository
    * @var OrderRepository
	*/
	
	private $cartRepository;
    private $productRepository;
	private $jwt;
	
	public function __construct(CartRepository $cartRepository, ProductRepository $productRepository, OrderRepository $orderRepository){
		$this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
		$this->orderRepository = $orderRepository;
		$this->jwt = new JWT();
	}

	/**
	* @Route("/api/orders", name="orders", methods={"GET", "DELETE"})
	* @return JsonResponse
	*/
	public function order (Request $request): Response{
		if($this->jwt->authorization($request->headers->all()) !== "OK"){
			return $this->jwt->authorization($request->headers->all());
		}
        $headers = $request->headers->all();
        $token = $headers['authorization'][0];
        $userId = $this->jwt->getUserIdFromToken($token);
        $orders = $this->orderRepository->findBy(['userId' => $userId]);
        $entityManager = $this->getDoctrine()->getManager();
        if(!isset($orders)){
            return new JsonResponse(['data'=> 'You don\'t have any order pending.'], 200); 
        }
        if($request->isMethod('DELETE')){
            foreach($orders as $order){
                $entityManager->remove($order);
            }
            $entityManager->flush();
		
			return new JsonResponse(['data'=> 'Your orders have been cancelled.'], 204);
        }

		$arr = [];
		
		for($i=0; $i<sizeof($orders); $i++){
            $id = $orders[$i]->getID();
            $totalPrice = $orders[$i]->getTotalPrice();
            $products = $orders[$i]->getProducts();
            $date = $orders[$i]->getCreationDate();
            $obj = (object) [
                'id'=> $id,
                'totalPrice'=>$totalPrice,
                'creationDate'=>$date,
                'products'=>$products
            ];
                array_push($arr, $obj);
		}
        return new JsonResponse(['data'=> $arr], 200); 
    }

    /**
	* @Route("/api/order/{orderId}", name="orderById", methods={"GET", "DELETE"})
	* @return JsonResponse
	*/
	public function orderById (Request $request, int $orderId): Response{
		if($this->jwt->authorization($request->headers->all()) !== "OK"){
			return $this->jwt->authorization($request->headers->all());
		}
        $order = $this->orderRepository->find($orderId);
        $entityManager = $this->getDoctrine()->getManager();
        if(!isset($order)){
            return new JsonResponse(['data'=> 'There is no order with that id.'], 200); 
        }
        if($request->isMethod('DELETE')){
			$entityManager->remove($order);
			$entityManager->flush();
		
			return new JsonResponse(['data'=> 'The order '.$orderId.' has been cancelled.'], 204);
        }

        if($order === null){
            return new JsonResponse(['data'=> 'Order not found'], 200); 
        }


        $obj = (object) [
            'id'=> $order->getID(),
            'totalPrice'=>$order->getTotalPrice(),
			'creationDate'=>$order->getCreationDate(),
            'products'=>$order->getProducts()
        ];
        return new JsonResponse(['data'=> $obj], 200); 
    }

}