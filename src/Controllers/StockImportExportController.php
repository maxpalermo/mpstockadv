<?php

namespace MpSoft\MpStockAdv\Controllers;

use MpSoft\MpStockAdv\Helpers\MenuDataHelper;
use MpSoft\MpStockAdv\Helpers\StockMvtImportHelper;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StockImportExportController extends FrameworkBundleAdminController
{
    protected MenuDataHelper $menuDataHelper;
    /**
     * @Route("/admin/mpstockadv/stock-import-export", name="mpstockadv_admin_stockimportexport", methods={"GET"})
     */
    public function index(): Response
    {
        $this->menuDataHelper = new MenuDataHelper();
        $this->menuDataHelper->setTitle('Movimenti di magazzino');
        $this->menuDataHelper->setIcon('inventory');
        $this->menuDataHelper->injectMenuData(
            [
                [
                    'icon' => 'inventory',
                    'label' => 'Movimenti',
                    'children' => [
                        [
                            'icon' => 'add',
                            'label' => 'Nuovo Movimento',
                            'href' => 'javascript:newMovement();',
                        ],
                        [
                            'icon' => 'download',
                            'label' => 'Importa Giacenze',
                            'href' => 'javascript:confirmImport();',
                        ],
                    ],
                ],
                [
                    'icon' => 'settings',
                    'label' => 'Impostazioni',
                    'children' => [
                        [
                            'icon' => 'tune',
                            'label' => 'Preferenze',
                            'href' => 'javascript:showModalPreferences();',
                        ],
                        [
                            'icon' => 'lock_open',
                            'label' => 'Permessi',
                            'href' => 'javascript:showModalPermissions();',
                        ],
                    ],
                ],
            ]
        );
        return $this->render(
            '@Modules/mpstockadv/views/twig/Controllers/StockImportExportController.index.html.twig',
            [
                'toolbarMenuHtml' => $this->menuDataHelper->renderMenu(),
            ]
        );
    }

    public function parseXmlAction(Request $request): JsonResponse
    {
        $file = $request->files->get('xml_file');
        /*
            returns
            [
                test = false
                originalName = "S(960-20220316)101436.XML"
                mimeType = "text/xml"
                error = 0
                *SplFileInfo*pathName = "/tmp/phpbdb7HT"
                *SplFileInfo*fileName = "phpbdb7HT"
            ]
        */

        //$file = \Tools::fileAttachment('xml_file');
        /*
            returns 
            [                        
                rename = "6864f689cebc86.xml" 
                tmp_name = "/tmp/phpYVRnmJ"
                name = "S(960-20220316)101436.XML"
                mime = "text/xml"
                error = 0
                size = 1342
            ]
        */

        if ($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            $filePath = $file->getPathname();
            $fileName = $file->getClientOriginalName();
        }

        /** @var \MpSoft\MpStockAdv\Helpers\StockMvtImportHelper */
        $xmlParser = new StockMvtImportHelper();

        $parsed = $xmlParser->parseStockMovementFile($fileName, $filePath);

        return new JsonResponse([
            'success' => true,
            'message' => 'Importazione movimenti da XML eseguita',
            'content' => $parsed,
        ]);
    }

    /**
     * @Route("/admin/mpstockadv/import/mvt-reason", name="mpstockadv_admin_stockimportexport_import_mvt_reason", methods={"POST"})
     */
    public function importMvtReason(Request $request): JsonResponse
    {
        // TODO: implementare importazione reale
        $file = $request->files->get('mvt_reason_file');
        $type = $request->request->get('mvt_reason_type');

        if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            return new JsonResponse([
                'success' => false,
                'message' => 'File non valido',
            ]);
        }

        $mimeType = $file->getMimeType();
        if ('reasons' === $type && !in_array($mimeType, ['text/csv', 'application/csv', 'text/plain'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'File non valido per l\'opzione selezionata',
                'type' => $type,
                'mimeType' => $mimeType,
            ]);
        }

        $fileContent = file_get_contents($file->getPathname());
        $list = [];

        switch ($type) {
            case 'reasons':
                $result = \MpSoft\MpStockAdv\Models\ModelStockMvtReason::importFromCsv($fileContent, $type);

                break;
            case 'lang':
                $result = \MpSoft\MpStockAdv\Models\ModelStockMvtReason::importFromCsv($fileContent, $type);

                break;
            default:
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Tipo non valido',
                    'type' => $type,
                ]);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Importazione tipi di movimento eseguita',
            'type' => $type,
            'content' => $result,
        ]);
    }

    /**
     * @Route("/admin/mpstockadv/import/orders", name="mpstockadv_admin_stockimportexport_import_orders", methods={"POST"})
     */
    public function importOrders(Request $request): JsonResponse
    {
        // TODO: implementare importazione reale
        return new JsonResponse([
            'success' => true,
            'message' => 'Importazione movimenti da ordini eseguita (mock)',
        ]);
    }

    /**
     * @Route("/admin/mpstockadv/import/xml", name="mpstockadv_admin_stockimportexport_import_xml", methods={"POST"})
     */
    public function importXml(Request $request): JsonResponse
    {
        // TODO: implementare importazione reale
        // $file = $request->files->get('xml_file');
        return new JsonResponse([
            'success' => true,
            'message' => 'Importazione movimenti da XML eseguita (mock)',
        ]);
    }
}
