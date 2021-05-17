<?php
namespace App\Controller;
Use App\Entity\User;
use App\Repository\UserRepository;
use App\Tools\JWT;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class UserController extends AbstractController{
	
	/**
	* @var UserRepository
	*/
	
	private $repository;
    private $jwt;
	
	public function __construct(UserRepository $repository){
        $this->repository = $repository;
	}
	
	/**
     * @Route("/api/register", name="register",  methods={"POST"})
     * @return JsonResponse
     */
    
    public function register (Request $request): JsonResponse{
        $content = $request->getContent();
        $data = json_decode($content);

        if(!isset($data->login)){
            return new JsonResponse(['error' => 'login required.'], 400);
        }

        if(!isset($data->email)){
            return new JsonResponse(['error' => 'email required.'], 400);
        }

        if(!isset($data->password)){
            return new JsonResponse(['error' => 'password required.'], 400);
        }

        if(!isset($data->firstname)){
            return new JsonResponse(['error' => 'firstname required.'], 400);
        }

        if(!isset($data->lastname)){
            return new JsonResponse(['error' => 'lastname required.'], 400);
        }

        if(count($this->repository->findBy(array('login'=>$data->login)))>0){
            return new JsonResponse(['error' => 'Username already taken.'], 400);
        } else if (count($this->repository->findBy(array('email'=>$data->email)))>0){
            return new JsonResponse(['error' => 'email already used.'], 400);
        } else {
            try {
                $user = new User;
                $user->setLogin($data->login);
                $user->setPassword(password_hash($data->password, PASSWORD_DEFAULT));
                $user->setEmail($data->email);
                $user->setFirstname($data->firstname);
                $user->setLastname($data->lastname);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
                return new JsonResponse(['data' => $user->getLogin().' has been registered'], 201);
            } catch (Exception $e) {
                return new JsonResponse(['error' => $e]);
            }
        }
	}

   	/**
     * @Route("/api/login", name="login",  methods={"POST"})
     * @return JsonResponse
     */

    public function login (Request $request): JsonResponse{
        $content = $request->getContent();
        $data = json_decode($content);
        if(!isset($data->email)){
            return new JsonResponse(["error" => "An email is required."], 401);
        } else if(!isset($data->password)) {
            return new JsonResponse(["error" => "A password is required."], 401);
        } else {
            $userArrayByEmail = $this->repository->findBy(array('email'=>$data->email));
            if(!empty($userArrayByEmail)){
                $user = $userArrayByEmail[0];
                if(password_verify($data->password, $user->getPassword())){
                    $this->jwt = new JWT();
                    $token = $this->jwt->getToken($user->getId());
                    return new JsonResponse(["token" => $token], 200);
                } else {
                    return new JsonResponse(['error' => "wrong password"], 401);
                }
            } else {
                return new JsonResponse(["error" => "email unknown"], 401);
            }
        }
    }

    /**
     * @Route("/api/user", name="user", methods={"GET", "PUT", "DELETE"})
     * @return JsonRepsonse
    */

    public function updateUser(Request $request): JsonResponse{
		$content = $request->getContent();
        $data = json_decode($content);
        $this->jwt = new JWT();
        
		if($this->jwt->authorization($request->headers->all()) !== "OK"){
            return $this->jwt->authorization($request->headers->all());
		}
        $headers = $request->headers->all();
        $token = $headers['authorization'][0];
        $userId = $this->jwt->getUserIdFromToken($token);
        $user = $this->repository->find($userId);

        if($request->isMethod('PUT')){
            if(!isset($data->passwordVerification)) {
                return new JsonResponse(["error" => "Your current password is required."], 401);
            }
            if(password_verify($data->passwordVerification, $user->getPassword())){
                $entityManager = $this->getDoctrine()->getManager();
                if(isset($data->login)){
                    $user->setLogin($data->login);
                }
                if(isset($data->email)){
                    $user->setEmail($data->email);
                }
                if(isset($data->password)){
                    $user->setPassword(password_hash($data->password, PASSWORD_DEFAULT));
                }
                if(isset($data->firstname)){
                    $user->setFirstName($data->firstname);
                }
                if(isset($data->lastname)){
                    $user->setLastName($data->lastname);
                }
    
                $entityManager->flush();
    
                $obj = (object) [
                    'login'=> $user->getLogin(),
                    'email'=>$user->getEmail(),
                    'firstname'=>$user->getFirstName(),
                    'lastname'=>$user->getLastName(),
                ];
    
                return new JsonResponse(['data'=> $user->getLogin().' has been updated', 'user'=>$obj], 200);
            } else {
                return new JsonResponse(['error' => "wrong password"], 401);
            }
        }

        if($request->isMethod('DELETE')){
            if(!isset($user)){
                return new JsonResponse(['error'=> 'This user has already been deleted'], 400);
            }
            if(!isset($data->passwordVerification)) {
                return new JsonResponse(["error" => "Your current password is required."], 401);
            }
            if(password_verify($data->passwordVerification, $user->getPassword())){
                $name = $user->getLogin();
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->remove($user);
                $entityManager->flush();
                return new JsonResponse(['data'=> $name.' has been deleted'], 204);
            } else {
                return new JsonResponse(['error' => "wrong password"], 401);
            }
        }


        $obj = (object) [
            'login'=> $user->getLogin(),
            'email'=>$user->getEmail(),
            'firstname'=>$user->getFirstName(),
            'lastname'=>$user->getLastName(),
        ];

        return new JsonResponse(['user'=>$obj], 200);
    }
}
