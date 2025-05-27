<?php

namespace MpSoft\MpStockAdv\Controller\Admin;

use MpSoft\MpStockAdv\Helpers\ParseCsv;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StockImportExportController extends FrameworkBundleAdminController
{
    /**
     * @Route("/admin/mpstockadv/stock-import-export", name="mpstockadv_admin_stockimportexport", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('@Modules/mpstockadv/views/PrestaShop/Admin/mpstockadv/stock_import_export.html.twig');
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
