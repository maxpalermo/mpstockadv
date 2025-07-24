<?php

namespace MpSoft\MpStockAdv\Controllers;

use MpSoft\MpStockAdv\Helpers\GetDocuments;
use MpSoft\MpStockAdv\Helpers\MenuDataHelper;
;
use MpSoft\MpStockAdv\Helpers\OrderToJsonHelper;
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
        $id_lang = (int) \Context::getContext()->language->id;
        $this->menuDataHelper = new MenuDataHelper();
        $this->menuDataHelper->setTitle('Importazione/Esportazione movimenti');
        $this->menuDataHelper->setIcon('upload download');
        $this->menuDataHelper->initMenu();
        return $this->render(
            '@Modules/mpstockadv/views/twig/Controllers/StockImportExportController.index.html.twig',
            [
                'toolbarMenuHtml' => $this->menuDataHelper->renderMenu(),
                'order_states' => \OrderState::getOrderStates($id_lang),
                'document_types' => [
                    'order' => [
                        'id' => (int) \Configuration::get('MPSTOCKADV_TYPE_ORDER'),
                        'name' => 'Ordine',
                    ],
                    'invoice' => [
                        'id' => (int) \Configuration::get('MPSTOCKADV_TYPE_INVOICE'),
                        'name' => 'Fattura'
                    ],
                    'delivery' => [
                        'id' => (int) \Configuration::get('MPSTOCKADV_TYPE_DELIVERY'),
                        'name' => 'Nota ordine'
                    ],
                    'return' => [
                        'id' => (int) \Configuration::get('MPSTOCKADV_TYPE_RETURN'),
                        'name' => 'Reso'
                    ],
                    'credit_slip' => [
                        'id' => (int) \Configuration::get('MPSTOCKADV_TYPE_CREDIT_SLIP'),
                        'name' => 'Nota credito'
                    ]
                ],
                'payment_modules' => (function () {
                    $paymentModules = \PaymentModule::getPaymentModules();
                    $paymentModules = array_map(function ($module) {
                        return $module['name'];
                    }, $paymentModules);
                    if (!in_array('mpcodfee', $paymentModules)) {
                        $paymentModules[] = 'mpcodfee';
                    }
                    return $paymentModules;
                })(),
                // Gestione sicura del valore JSON della configurazione
                'payment_modules_selected' => (function () {
                    $selectedModulesJson = \Configuration::get("MPSTOCKADV_PAYMENT_MODULES");
                    $selectedModules = [];
                    if (!empty($selectedModulesJson)) {
                        try {
                            $selectedModules = json_decode($selectedModulesJson, true, 512, JSON_THROW_ON_ERROR);
                        } catch (\JsonException $e) {
                            $selectedModules = [];
                            // Facoltativo: log dell'errore
                        }
                    }
                    return $selectedModules;
                })(),
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

    public function exportOrderAction(Request $request): JsonResponse
    {
        $id_orders = $request->get('ids');
        $type = $request->get('typeDocument');

        if (in_array($type, ['order', 'invoice', 'delivery', 'return', 'credit_slip'])) {
            $typeKey = \Tools::strtoupper("MPSTOCKADV_TYPE_{$type}");
            $type = (int) \Configuration::get($typeKey);
        }

        $invoiceXml = new OrderToJsonHelper();
        $invoice = $invoiceXml->createDocuments($type, $id_orders);

        return $this->json([
            'success' => true,
            'message' => 'Esportazione movimenti da ordini eseguita (mock)',
            'ids' => $id_orders,
            'type' => $type,
            'document' => $invoice,
        ]);
    }

    protected function prepareDocument($id_order, $type)
    {
        $order2Json = new OrderToJsonHelper();
        $order2Json->createDocuments($type, [$id_order]);
        return $order2Json->getDocument();
    }

    public function setMvtReasonIdAction(Request $request)
    {
        $content = $request->getContent();
        $jsonData = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $id_type_order = (int) $jsonData['id_type_order'] ?? 0;
        $id_type_invoice = (int) $jsonData['id_type_invoice'] ?? 0;
        $id_type_delivery = (int) $jsonData['id_type_delivery'] ?? 0;
        $id_type_return = (int) $jsonData['id_type_return'] ?? 0;
        $id_type_credit_slip = (int) $jsonData['id_type_credit_slip'] ?? 0;

        \Configuration::updateValue("MPSTOCKADV_TYPE_ORDER", $id_type_order);
        \Configuration::updateValue("MPSTOCKADV_TYPE_INVOICE", $id_type_invoice);
        \Configuration::updateValue("MPSTOCKADV_TYPE_DELIVERY", $id_type_delivery);
        \Configuration::updateValue("MPSTOCKADV_TYPE_RETURN", $id_type_return);
        \Configuration::updateValue("MPSTOCKADV_TYPE_CREDIT_SLIP", $id_type_credit_slip);

        return $this->json([
            'success' => true,
            'message' => 'ID tipi di movimento aggiornati',
        ]);
    }

    public function getMvtReasonIdAction()
    {
        return $this->json(
            [
                'success' => true,
                'document_types' => [
                    'order' => [
                        'id' => (int) \Configuration::get('MPSTOCKADV_TYPE_ORDER'),
                        'name' => 'Ordine',
                    ],
                    'invoice' => [
                        'id' => (int) \Configuration::get('MPSTOCKADV_TYPE_INVOICE'),
                        'name' => 'Fattura'
                    ],
                    'delivery' => [
                        'id' => (int) \Configuration::get('MPSTOCKADV_TYPE_DELIVERY'),
                        'name' => 'Nota ordine'
                    ],
                    'return' => [
                        'id' => (int) \Configuration::get('MPSTOCKADV_TYPE_RETURN'),
                        'name' => 'Reso'
                    ],
                    'credit_slip' => [
                        'id' => (int) \Configuration::get('MPSTOCKADV_TYPE_CREDIT_SLIP'),
                        'name' => 'Nota credito'
                    ]
                ]
            ]
        );
    }

    public function searchDocumentsAction(Request $request)
    {
        $content = $request->getContent();
        $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        if ($json) {
            $idOrderStates = $json['idOrderStates'];
            $idTypeDocument = $json['idTypeDocument'];
            $page = (int) $json['page'];
            $perPage = (int) $json['perPage'];

            $getDocuments = new GetDocuments($page, $perPage, $idOrderStates, $idTypeDocument);
            $result = $getDocuments->run();

            $twigData = $this->renderView(
                '@Modules/mpstockadv/views/twig/Controllers/StockImportExport/tbody.html.twig',
                ['rows' => $result['rows']]
            );


            if ($result['pagination']) {
                $twigPagination = $this->renderView(
                    '@Modules/mpstockadv/views/twig/Components/pagination.html.twig',
                    [
                        'pagination' => $result['pagination']
                    ]
                );
            }

            return $this->json([
                'success' => true,
                'data' => $twigData,
                'pagination' => $twigPagination ?? '',
            ]);
        }
    }

    public function exportDocumentsAction(Request $request)
    {
        $content = $request->getContent();
        $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $ids = $json['ids'];
        $type = $json['typeDocument'];

        if (in_array($type, ['order', 'invoice', 'delivery', 'return', 'credit_slip'])) {
            $typeKey = \Tools::strtoupper("MPSTOCKADV_TYPE_{$type}");
            $type = (int) \Configuration::get($typeKey);
        }

        $orderXml = new OrderToJsonHelper();
        $document = $orderXml->createDocuments($type, $ids);
        $xml = $orderXml->toXml();

        return $this->json([
            'success' => true,
            'document' => $document,
            'xml' => $xml,
        ]);
    }

    public function savePaymentModulesAction(Request $request)
    {
        $content = $request->getContent();
        $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $ids = $json['ids'];
        \Configuration::updateValue("MPSTOCKADV_PAYMENT_MODULES", json_encode($ids));
        return $this->json([
            'success' => true,
        ]);
    }
}
