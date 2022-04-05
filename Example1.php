<?php

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Entity\Project;

class ProjectApiController extends Controller
{
    /**
     * @Route("/project_identifier", name="project_identifier")
     */
    public function getProjectIdentifier(Request $request)
    {
        $response = [];

        if ($apiKey = $request->query->get('api_key')) {
            $project = $this->getDoctrine()
                ->getRepository(Project::class)
                ->findOneByApiKey($apiKey);

            if ($project) {
                if ($project->checkIfUrlMatchesAllowedDomains($_SERVER["HTTP_REFERER"] ?? null)) {
                    $response['project_identifier'] = $project->getIdentifier();
                } else {
                    $response['error'] = 'DOMAIN_NOT_ALLOWED';
                }
            } else {
                $response['error'] = 'PROJECT_NOT_FOUND';
            }
        } else {
            $response['error'] = 'MISSING_API_KEY';
        }

        return new JsonResponse($response);
    }
}