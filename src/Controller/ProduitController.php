<?php
/**
 * Created by PhpStorm.
 * User: etter
 * Date: 05/03/2019
 * Time: 10:44
 */

namespace App\Controller;


use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Produit;
use App\Form\ProduitType;


class ProduitController extends AbstractController
{
    /**
     * @Route("/", name="product_index")
     */
    public function index()
    {
        return $this->redirectToRoute('product_list', [
            'page' => 1
        ]);
    }
    /**
     * @Route("/list/{page}", name="product_list")
     */
    public function list($page, PaginatorInterface $paginator)
    {
        // Retrieve the entity manager of Doctrine
        $em = $this->getDoctrine()->getManager();
        // Get some repository of data
        $produitRepository = $em->getRepository(Produit::class);

        // Find all the data on the Produit table
        $produitsQuery = $produitRepository->createQueryBuilder('p')
            ->getQuery();

        // Paginate the results of the query
        $produits = $paginator->paginate(
            // Doctrine Query
            $produitsQuery,
            // Define the page parameter
            $page,
            // Items per page
            5
        );

        return $this->render('Produit/index.html.twig', array(
            'produits' => $produits
        ));
    }
    /**
     * @Route("/view/{id}", name="product_show")
     */
    public function show($id, \Swift_Mailer $mailer)
    {
        // Find product with the given ID
        $produit = $this->getDoctrine()
            ->getRepository(Produit::class)
            ->find($id);

        if (!$produit) {
            // If no product has been found => display error msg
            $this->addFlash('error', 'Le numero du produit n\'existe pas.');
            // And we comeback to index
            return $this->redirectToRoute('product_index');
        }
        // or render a template
         return $this->render('produit/show.html.twig',
             ['produit' => $produit]);
    }


    /**
     * @Route("/create", name="product_add")
     */
    public function create(Request $request, \Swift_Mailer $mailer)
    {
        //  Create a new product
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);

        // If the request is in POST
        if ($request->isMethod('POST')) {
            // We make the link Request <-> Form
            // From now on, the variable $produit contains the values ​​entered in the form by the visitor
            $form->handleRequest($request);

            // We check that the values ​​entered are correct
            if ($form->isValid()) {
                // We register our object $ advert in the database, for example
                $em = $this->getDoctrine()->getManager();
                $em->persist($produit);
                $em->flush();
                // Display a success msg
                $this->addFlash('success', 'Produit bien enregistrée.');
                // Send mail for the creation product
                $this->sendMail($mailer, $produit);
                // We redirect to the viewing page of the newly created product
                return $this->redirectToRoute('product_show', array('id' => $produit->getId()));
            }
        }

        // At this point, the form is invalid because:
        // - Either the request is of type GET, so the visitor has just arrived on the page and wants to see the form
        // - Either the request is of type POST, but the form contains invalid values, so it is displayed again
        return $this->render('Produit/create.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Send mail for the creation product
     * @param \Swift_Mailer $mailer
     * @param Produit $produit
     * @return int
     */
    private function sendMail(\Swift_Mailer $mailer, Produit $produit)
    {
        // Retrieve email sender
        $from = $this->getParameter('mailer.from');
        $to = $this->getParameter('mailer.to');
        // Build the mail
        $message = (new \Swift_Message('Un nouveau produit est cree'))
            ->setFrom($from)
            ->setTo($to)
            ->setBody(
                $this->renderView(
                    'emails/productCreated.html.twig',
                    ['produit' => $produit]
                ),
                'text/html'
            )
        ;
        // Send the mail
        return $mailer->send($message);
    }
}
