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

class CartController extends AbstractController{
	
	/**
	* @var ProductRepository
    * @var CartRepository
    * @var OrderRepository
	*/
	
	private $cartRepository;
    private $productRepository;
	private $jwt;

    public function setUp(): void
    {
        self::bootKernel();
    }
	
	public function __construct(CartRepository $cartRepository, ProductRepository $productRepository){
		$this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
		$this->jwt = new JWT();
	}

    /**
	* @Route("/api/cart/{productId}", name="addToCart", methods={"PUT", "DELETE"})
	* @return JsonResponse
	*/
	
	public function addToCart (Request $request, int $productId): Response{
		$content = $request->getContent();
        $data = json_decode($content);
		if($this->jwt->authorization($request->headers->all()) !== "OK"){
			return $this->jwt->authorization($request->headers->all());
		}
		$product = $this->productRepository->find($productId);
        $headers = $request->headers->all();
        $token = $headers['authorization'][0];
        $userId = $this->jwt->getUserIdFromToken($token);
        $cart = $this->cartRepository->findOneBy(['userId' => $userId]);
        $entityManager = $this->getDoctrine()->getManager();
        if(!isset($product)){
            return new JsonResponse(['error'=> 'product not found'], 404); 
        }

        if($request->isMethod('DELETE')){
            if(!isset($cart)){
                return new JsonResponse(['error'=> 'Your cart is empty.'], 404); 
            }
			$entityManager->flush();
            $newProductArray = $cart->getProducts();

            foreach($newProductArray as $key => $item){
                if($newProductArray[$key]->id===$productId){
                    $price = $newProductArray[$key]->price;
                    unset($newProductArray[$key]);
                    $cart->setProducts($newProductArray);
                    $cart->setTotalPrice($cart->getTotalPrice()-$price);
                    $entityManager->flush();
                    return new JsonResponse(['data'=> 'Product removed for your cart.', 'cart'=>$cart], 200);
                }
            }
			return new JsonResponse(['error'=> 'Product not found in your cart.'], 404);
        }

        if(!isset($cart)){
            $cart = new Cart;
            $cart->setUserId($userId);
            $cart->settoTalPrice(0);
            $cart->setProducts([]);
            $entityManager->persist($cart);
            $entityManager->flush();
        }

        $obj = (object) [
            'id'=> $product->getId(),
            'name'=> $product->getName(),
            'description'=>$product->getDescription(),
            'photo'=>$product->getPhoto(),
            'price'=>$product->getPrice(),
        ];
    
        $newProductArray = $cart->getProducts();
        array_push($newProductArray, $obj);
        $cart->setProducts($newProductArray);
        $cart->setTotalPrice($cart->getTotalPrice()+$product->getPrice());
        $entityManager->flush();
        return new JsonResponse(['data'=> $product->getName().' has been added to your cart'], 301);
    }

    /**
	* @Route("/api/cart", name="cart", methods={"GET", "DELETE"})
	* @return JsonResponse
	*/
	public function cart (Request $request): Response{
		if($this->jwt->authorization($request->headers->all()) !== "OK"){
			return $this->jwt->authorization($request->headers->all());
		}
        $headers = $request->headers->all();
        $token = $headers['authorization'][0];
        $userId = $this->jwt->getUserIdFromToken($token);
        $cart = $this->cartRepository->findOneBy(['userId' => $userId]);
        $entityManager = $this->getDoctrine()->getManager();
        if(!isset($cart)){
            return new JsonResponse(['error'=> 'Your cart is empty.'], 404); 
        }
        if($request->isMethod('DELETE')){
			$entityManager->remove($cart);
			$entityManager->flush();
		
			return new JsonResponse(['data'=> 'Your cart has been emptied.'], 204);
        }

        $id = $cart->getID();
        $totalPrice = $cart->getTotalPrice();
        $products = $cart->getProducts();
        $obj = (object) [
            'id'=> $id,
            'totalPrice'=>$totalPrice,
            'products'=>$products
        ];
        return new JsonResponse(['data'=> $obj], 200); 
    }

    /**
	* @Route("/api/cart/validate", name="validateCart", methods={"POST"})
	* @return JsonResponse
	*/

	public function validateCart (Request $request): Response{
		if($this->jwt->authorization($request->headers->all()) !== "OK"){
			return $this->jwt->authorization($request->headers->all());
		}
        $headers = $request->headers->all();
        $token = $headers['authorization'][0];
        $userId = $this->jwt->getUserIdFromToken($token);
        $cart = $this->cartRepository->findOneBy(['userId' => $userId]);
        $entityManager = $this->getDoctrine()->getManager();
        if(!isset($cart)){
            return new JsonResponse(['error'=> 'Your cart is empty.'], 404); 
        }

        $products = $cart->getProducts();

        $order = new Order;
        $order->setUserId($userId);
        $order->settoTalPrice($cart->getTotalPrice());
        $order->setProducts($cart->getProducts());
        $order->setCreationDate(date("Y-m-d H:i:s"));
        $entityManager->persist($order);
        $entityManager->remove($cart);
        $entityManager->flush();
    
        return new JsonResponse(['data'=> 'Your order has been registered.'], 201);
    }
}
