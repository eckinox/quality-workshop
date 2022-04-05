<?php

namespace App\Controller\Client;

use App\Library\Symfony\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Library\Symfony\Annotation\Lang;
use App\Entity\Quotation\Quote;
use App\Entity\Quotation\Rental;
use App\Entity\Quotation\Sale;
use App\Entity\Application\Note;
use App\Entity\Quotation\Workorder;
use App\Form\Quotation\WorkorderType;

/**
 * @Lang(domain="client", default_key="client")
 */
class ClientController extends Controller
{
    /**
     * @Route("/view/project/{project_id}", name="view_project", requirements={"project_id"="\d+"})
     */
    public function viewProject(Request $request, $project_id)
    {
        $user = $this->getUser();
        $securityRedirect = true;
        $project = $this->getDoctrine()->getRepository(Quote::class)->find($project_id);

        foreach ($user->getUserOrganization() as $userOrg) { // check if the user has the has the privilege to view the project
            if ($userOrg->getContact() == null) {
                if ($userOrg->getOrganization()->getId() == $project->getClient()->getId()) {
                    $securityRedirect = false;
                }
            } elseif ($userOrg->getContact() == $project->getClientContact()) {
                $securityRedirect = false;
            }
        }

        if ($securityRedirect == true) {
            return $this->redirectToRoute("index_organization_project");
        }

        $rentals = $this->getDoctrine()
            ->getRepository(Rental::class)
            ->findByQuote($project->getId(), [], "p.number");

        $sales = $this->getDoctrine()
            ->getRepository(Sale::class)
            ->findByQuote($project->getId(), [], "p.number");

        $notes = $this->getDoctrine()
            ->getRepository(Note::class)
            ->findByQuote($project->getId());

        $workorders = $this->getDoctrine()
            ->getRepository(Workorder::class)
            ->findByQuote($project->getId());

        $workordersWorkforces = [
            "simple_time" => 0,
            "time_and_one_half" => 0,
            "double_time" => 0,
        ];

        foreach ($workorders as $workorder) {
            foreach ($workorder->getWorkforces() as $wf) {
                foreach (['simple_time_number', 'double_time_number', 'time_and_one_half_number'] as $k) {
                    if ($wf[$k]) {
                        $time_type = str_replace('_number', '', $k);
                        $hours = floatval($wf[$k]);
                        $workers = floatval($wf['workers_number']);
                        $days =floatval($wf['days_number']);

                        $workordersWorkforces[$time_type] += ($hours * $workers * $days);
                    }
                }
            }
        }
        return $this->renderModView('client/projects/edit.html.twig', array(
            'js_lang' => $this->lang_array('client.javascript'),
            'title' => $this->lang('title.view_organization_project', ['%number%' => $project->getNumber()]),
            'project' => $project,
            'quote' => $project, //tmp fix till the workorders are changed
            'notes' => $notes,
            'rentals' => $rentals,
            'sales' => $sales,
            'places' => $this->data_to_lang("quotation.places", "quotation"),
            'workforcesTypes' => $this->data_to_lang("quotation.workforces.types", "quotation"),
            'purchaseOrder' => $project->getClient()->getPurchaseOrdersNo(),
            'purchaseOrderAmountLeft' => $project->getClient()->getPurchaseOrdersAmountLeft($project->getPurchaseOrder()),
            'bt' => $project->getClient()->getBtNumbers(),
            'workorderListing' => WorkorderType::getListing($this, "project"),
            'workordersWorkforces' => $workordersWorkforces,
            'breadcrumbVariables' => [
                'view_project' => [
                    '%number%' => $project->getNumber()
                ],
                'parent' => 'index_organization_project',
            ],
        ), $request);
    }
}