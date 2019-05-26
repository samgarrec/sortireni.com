<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Serie;
use App\Entity\Sortie;
use App\Form\ChangePasswordType;
use App\Form\ParticipantType;
use App\Form\SortieType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserController extends Controller
{
    /**
     * @Route("/", name="login")
     */
    /**
     * on nomme la route login car dans le fichier
     * security.yaml on a login_path: login
     * @Route("/", name="login")
     */
    public function login()
    {

        // Le service authentication_utils permet de récupérer le nom d'utilisateur
        // et l'erreur dans le cas où le formulaire a déjà été soumis mais était invalide
        // (mauvais mot de passe par exemple)
        $authenticationUtils = $this->get('security.authentication_utils');
        if($authenticationUtils->getLastAuthenticationError()){

            $error='mauvais identifiants';
        }else{

            $error=null;

        }
        return $this->render("user/login.html.twig", ['error' => $error]);
    }

    /**
     * Symfony gère entierement cette route il suffit de l'appeler logout.
     * Penser à paramètrer le fichier security.yaml pour rediriger la déconnexion.
     * @Route("/logout", name="logout")
     */
    public function logout(){}



    /**
     * La fonction register() permet d'enregistrer un nouveau participant dans la BDD
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param UserPasswordEncoderInterface $encoder
     * @Route("/admin/register", name="participant-register")
     */
    public function register (Request $request, EntityManagerInterface $em, UserPasswordEncoderInterface $encoder)
    {
        $participant = new Participant();
        $registerForm =$this->createForm(ParticipantType:: class, $participant);
        $registerForm->handleRequest ($request);

        if ($registerForm->isSubmitted() && $registerForm->isValid())
        {
            $hashed=$encoder->encodePassword ($participant, $participant->getPassword());
            $participant->setPassword ($hashed);
            $em->persist($participant);
            $em->flush();
            $this->addFlash("success", "Vos informations ont bien été enregistrées");
            $this->redirectToRoute("monProfil");
        }

        return $this->render ("user/register.html.twig", ["registerForm"=>$registerForm->createView()]);
    }


    /**
     *
     * @Route("/registersortie")
     */
    public function registerSortie (Request $request, EntityManagerInterface $em)
    {
        $sortie = new Sortie();
        $registerForm =$this->createForm(SortieType:: class, $sortie);
        $registerForm->handleRequest ($request);

        if ($registerForm->isSubmitted() && $registerForm->isValid())
        {

            $em->persist($sortie);
            $em->flush();
            $this->addFlash("success", "Vos informations ont bien été enregistrées");
            $this->redirectToRoute("sortireni.com");
        }

        return $this->render ("registerSortie.html.twig", ["registerForm"=>$registerForm->createView()]);
    }



    /**
     *
     * @Route("/profil", name="monProfil")
     */
    public function updateProfile (Request $request, EntityManagerInterface $em, UserPasswordEncoderInterface $encoder)
    {
        $participant = $this->getUser();
        $registerForm =$this->createForm(ParticipantType:: class, $participant);
        $registerForm->handleRequest ($request);

        if ($registerForm->isSubmitted() && $registerForm->isValid())
        {
            $em->persist($participant);
            $em->flush();

            $this->addFlash("success", "Vos informations ont bien été enregistrées");
            $this->redirectToRoute("monProfil");

            $this->addFlash("success", "Vos modifications ont bien été prises en compte");
            $this->redirectToRoute("monProfil");

        }

        return $this->render ("user/profil.html.twig", ["registerForm"=>$registerForm->createView()]);
    }
    /**
     *
     * @Route("/profil/{id}", name="un-profil",requirements={"id":"\d+"})
     */
    public function showProfile (Participant $p)
    {


        $participant = $p;


                        return $this->render ("user/unprofil.html.twig", ["unProfil"=>$p]);







    }


    /**
     * @return mixed
     * @Route("/inscription/{id}", name="inscription")
     */
    public function inscription(Request $request, EntityManagerInterface $em, Sortie $s)
    {
        $user=$this->getUser();

        // On récupère les données contenues dans le SortieRepository
        $sortieRepository = $this->getDoctrine()->getRepository(Sortie::class);

        $s->addParticipant($user);
        $em->persist($s);
        $em->flush();
        $this->addFlash("notice", "Vos modifications ont bien été prises en compte");


        return $this->redirectToRoute('sortie');
    }

    /**
     * @return mixed
     * @Route("/desistement/{id}", name="desistement")
     */
    public function desistement(Request $request, EntityManagerInterface $em, Sortie $s)
    {
        $user=$this->getUser();

        // On récupère les données contenues dans le SortieRepository
        $sortieRepository = $this->getDoctrine()->getRepository(Sortie::class);

        $s->removeParticipant($user);
        $em->persist($s);
        $em->flush();

        return $this->redirectToRoute('sortie');
    }

    /**
     * @return mixed
     * @Route("/publish/{id}", name="publier")
     */
    public function publish(Request $request, EntityManagerInterface $em, Sortie $s)
    {
        $user=$this->getUser();
        $etatRepository = $em->getRepository('App:Etat');
        $etat = $etatRepository->find(2);
        $s->setEtat($etat);


        $em->persist($s);
        $em->flush();

        return $this->redirectToRoute('sortie');
    }

    /**
     * @return mixed
     * @Route("/resetPassword", name="changePassword")
     */
    public function changeUserPassword(Request $request,UserPasswordEncoderInterface $pwdEncoder)
    {

        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {



            $oldPassword = $request->request->get('change_password')['oldPassword'];

            // Si l'ancien mot de passe est bon
            if ($pwdEncoder->isPasswordValid($user, $oldPassword)) {
                dump($oldPassword);
                $newEncodedPassword = $pwdEncoder->encodePassword($user, $user->getPlainPassword());
                $user->setPassword($newEncodedPassword);

                $em->persist($user);
                $em->flush();

                $this->addFlash('notice', 'Votre mot de passe à bien été changé !');

                return $this->redirectToRoute('profile');
            } else {
                $form->addError(new FormError('Ancien mot de passe incorrect'));
            }
        }
            return $this->render('user/changePassword.html.twig',["form" => $form->createView()

            ]);
        }
        //si pas de form soumis on envoi vers la page de modification


}
