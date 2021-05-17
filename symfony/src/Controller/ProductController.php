<?php
namespace App\Controller;
Use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Tools\JWT;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ProductController extends AbstractController{
	
	/**
	* @var ProductRepository
	*/
	
	private $repository;
	private $jwt;
	
	public function __construct(ProductRepository $repository){
		$this->repository = $repository;
		$this->jwt = new JWT();
	}
	
	/**
	* @Route("/api/product", name="postProduct", methods={"POST"})
	* @return JsonResponse
	*/
	
	public function postProduct (Request $request): Response{
		$content = $request->getContent();
        $data = json_decode($content);
		if($this->jwt->authorization($request->headers->all()) !== "OK"){
			return $this->jwt->authorization($request->headers->all());
		}

        if(!isset($data->name)){
            return new JsonResponse(['error' => 'name required.'], 400);
        }

        if(!isset($data->description)){
            return new JsonResponse(['error' => 'description required.'], 400);
        }

        if(!isset($data->photo)){
            return new JsonResponse(['error' => 'photo required.'], 400);
        }

        if(!isset($data->price)){
            return new JsonResponse(['error' => 'price required.'], 400);
        }

        if(count($this->repository->findBy(array('name'=>$data->name)))>0){
            return new JsonResponse(['error' => 'product name already taken.'], 400);
        } else {
            try {
                $product = new Product;
                $product->setName($data->name);
                $product->setDescription($data->description);
                $product->setPhoto($data->photo);
                $product->setPrice($data->price);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($product);
                $entityManager->flush();
				$obj = (object) [
					'name'=> $product->getName(),
					'description'=>$product->getDescription(),
					'photo'=>$product->getPhoto(),
					'price'=>$product->getPrice()
				];
	
                return new JsonResponse(['data' => $product->getName().' has been registered', 'product'=> $obj], 201);
            } catch (Exception $e) {
                return new JsonResponse(['error' => $e], 400);
            }
        }

	}

	/**
	* @Route("/api/products", name="products", methods={"GET"})
	* @return JsonResponse
	*/
	
	public function products (Request $request): Response{
		$content = $request->getContent();
        $data = json_decode($content);
		if($this->jwt->authorization($request->headers->all()) !== "OK"){
			return $this->jwt->authorization($request->headers->all());
		}

		$products = $this->repository->findAll();
		$arr = [];
		
		for($i=0; $i<sizeof($products); $i++){
			$obj = (object) [
				"id" => $products[$i]->getId(),
				"name" => $products[$i]->getName(),
				"description" => $products[$i]->getDescription(),
				"photo" => $products[$i]->getPhoto(),
				"price" => $products[$i]->getPrice(),
			];
			array_push($arr, $obj);
		}
		return new JsonResponse(['data' => $arr], 200);
	}

	/**
	* @Route("/api/product/{productId}", name="productId", methods={"GET", "PUT", "DELETE"})
	* @return JsonResponse
	*/
	
	public function productId (Request $request, int $productId): Response{
		$content = $request->getContent();
        $data = json_decode($content);
		if($this->jwt->authorization($request->headers->all()) !== "OK"){
			return $this->jwt->authorization($request->headers->all());
		}
		$product = $this->repository->find($productId);

		if($request->isMethod('PUT')){
			$entityManager = $this->getDoctrine()->getManager();
			if(isset($data->name)){
				$product->setName($data->name);
			}
			if(isset($data->description)){
				$product->setDescription($data->description);
			}
			if(isset($data->photo)){
				$product->setPhoto($data->photo);
			}
			if(isset($data->price)){
				$product->setPrice($data->price);
			}
			$entityManager->flush();

			$obj = (object) [
				'name'=> $product->getName(),
				'description'=>$product->getDescription(),
				'photo'=>$product->getPhoto(),
				'price'=>$product->getPrice(),
			];
		
			return new JsonResponse(['data'=> $product->getName().' has been updated', 'product'=>$obj], 200);
        }

		if($request->isMethod('DELETE')){
			$entityManager = $this->getDoctrine()->getManager();
			$entityManager->remove($product);
			$entityManager->flush();
		
			return new JsonResponse(['data'=> $product->getName().' has been deleted'], 204);
        }

		if($product === null){
			return new JsonResponse(['data'=> 'Product not found'], 404);
		}
        $obj = (object) [
            'name'=> $product->getName(),
            'description'=>$product->getDescription(),
            'photo'=>$product->getPhoto(),
            'price'=>$product->getPrice(),
        ];

		return new JsonResponse(['data' => $obj], 200);
	}
}
