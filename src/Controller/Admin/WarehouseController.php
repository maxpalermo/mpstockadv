<?php

namespace MpSoft\MpStockAdv\Controller\Admin;

use MpSoft\MpStockAdv\Services\MenuDataService;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WarehouseController extends FrameworkBundleAdminController
{
    private $menuDataService;

    public function __construct(MenuDataService $menuDataService)
    {
        $this->menuDataService = $menuDataService;
    }

    /**
     * @Route("/admin/mpstockadv/warehouse", name="mpstockadv_admin_warehouse", methods={"GET"})
     */
    public function index(): Response
    {
        // Recupera tutti i magazzini
        $id_lang = (int) \Context::getContext()->language->id;
        $warehouses = [];
        $countries = \Country::getCountries($id_lang);

        foreach (\Warehouse::getWarehouses(true) as $w) {
            $warehouseObj = new \Warehouse($w['id_warehouse']);
            $warehouses[] = [
                'id' => (int) $warehouseObj->id,
                'name' => $warehouseObj->name,
                'location' => $warehouseObj->reference,
                'active' => !$warehouseObj->deleted,
            ];
        }
        $this->menuDataService->injectMenuData([
            'icon' => 'home',
            'label' => 'Magazzini',
            'children' => [
                [
                    'icon' => 'add',
                    'label' => 'Nuovo Magazzino',
                    'href' => 'javascript:void(0);',
                    'action' => 'showNewWarehouse',
                    'dialogId' => $idDialog ?? '',
                ],
                [
                    'icon' => 'download',
                    'label' => 'Importa',
                    'href' => 'javascript:void(0);',
                ],
            ],
        ]);

        $menuData = $this->menuDataService->getMenuData();

        return $this->render('@Modules/mpstockadv/views/templates/admin/warehouses/warehouses.index.html.twig', [
            'logo_src' => \Context::getContext()->shop->getBaseURL(true).'img/'.\Configuration::get('PS_LOGO'),
            'menuData' => $menuData,
            'warehouses' => $warehouses,
            'countries' => $countries,
        ]);
    }

    /**
     * @Route("/admin/mpstockadv/warehouses", name="mpstockadv_admin_warehouses_list", methods={"GET"})
     */
    public function listWarehouses(): Response
    {
        // Ottieni tutti i magazzini
        $warehouses = [];
        foreach (\Warehouse::getWarehouses(true) as $w) {
            $warehouseObj = new \Warehouse($w['id_warehouse']);
            $warehouses[] = [
                'id' => (int) $warehouseObj->id,
                'name' => $warehouseObj->name,
                'location' => $warehouseObj->reference, // reference usata come location fittizia (personalizza se serve)
                'active' => !$warehouseObj->deleted,
            ];
        }

        return $this->json(['success' => true, 'data' => $warehouses]);
    }

    /**
     * @Route("/admin/mpstockadv/warehouse", name="mpstockadv_admin_warehouse_create", methods={"POST"})
     */
    public function createWarehouse(): Response
    {
        $data = json_decode($this->get('request_stack')->getCurrentRequest()->getContent(), true);
        // Valori di default (da personalizzare)
        $warehouse = new \Warehouse();
        $warehouse->name = $data['name'] ?? '';
        $warehouse->reference = $data['location'] ?? '';
        $warehouse->id_currency = 1; // Default: 1 (EUR), personalizza se serve
        $warehouse->id_address = 1; // Default: 1, personalizza con id_address reale
        $warehouse->id_employee = 1; // Default: 1, personalizza con id_employee reale
        $warehouse->management_type = 'WA'; // Default: Media ponderata
        $warehouse->deleted = 0;
        if ($warehouse->add()) {
            // Collega al negozio corrente (shop)
            $id_shop = (int) \Context::getContext()->shop->id;
            \Db::getInstance()->insert('warehouse_shop', [
                'id_warehouse' => (int) $warehouse->id,
                'id_shop' => $id_shop,
            ]);

            return $this->json(['success' => true, 'message' => 'Magazzino creato', 'warehouse' => [
                'id' => (int) $warehouse->id,
                'name' => $warehouse->name,
                'location' => $warehouse->reference,
                'active' => true,
            ]]);
        } else {
            return $this->json(['success' => false, 'message' => 'Errore creazione magazzino']);
        }
    }

    /**
     * @Route("/admin/mpstockadv/warehouse/{id}", name="mpstockadv_admin_warehouse_update", methods={"PUT"})
     */
    public function updateWarehouse($id): Response
    {
        $data = json_decode($this->get('request_stack')->getCurrentRequest()->getContent(), true);
        $warehouse = new \Warehouse((int) $id);
        if (!\Validate::isLoadedObject($warehouse)) {
            return $this->json(['success' => false, 'message' => 'Magazzino non trovato']);
        }
        $warehouse->name = $data['name'] ?? $warehouse->name;
        $warehouse->reference = $data['location'] ?? $warehouse->reference;
        if ($warehouse->update()) {
            return $this->json(['success' => true, 'message' => 'Magazzino aggiornato', 'warehouse' => [
                'id' => (int) $warehouse->id,
                'name' => $warehouse->name,
                'location' => $warehouse->reference,
                'active' => !$warehouse->deleted,
            ]]);
        } else {
            return $this->json(['success' => false, 'message' => 'Errore aggiornamento magazzino']);
        }
    }

    /**
     * @Route("/admin/mpstockadv/warehouse/{id}/toggle", name="mpstockadv_admin_warehouse_toggle", methods={"PATCH"})
     */
    public function toggleWarehouse($id): Response
    {
        $warehouse = new \Warehouse((int) $id);
        if (!\Validate::isLoadedObject($warehouse)) {
            return $this->json(['success' => false, 'message' => 'Magazzino non trovato']);
        }
        $warehouse->deleted = $warehouse->deleted ? 0 : 1;
        if ($warehouse->update()) {
            return $this->json(['success' => true, 'message' => 'Stato magazzino aggiornato', 'id' => $id, 'active' => !$warehouse->deleted]);
        } else {
            return $this->json(['success' => false, 'message' => 'Errore aggiornamento stato']);
        }
    }

    /**
     * @Route("/admin/mpstockadv/warehouse/edit/{id}", name="mpstockadv_admin_warehouse_edit")
     */
    public function edit(\Symfony\Component\HttpFoundation\Request $request, $id)
    {
        $warehouse = \Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'warehouse` WHERE id_warehouse = '.(int) $id);
        if (!$warehouse) {
            throw $this->createNotFoundException('Magazzino non trovato');
        }

        $message = null;
        $message_type = null;

        // Recupera indirizzo associato (se esiste)
        $address = \Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'address` WHERE id_warehouse = '.(int) $id.' AND deleted = 0');

        // Recupera lista paesi
        $countries = [];
        $countryRes = \Db::getInstance()->executeS('SELECT id_country, name FROM `'._DB_PREFIX_.'country_lang` WHERE id_lang = 1 ORDER BY name');
        foreach ($countryRes as $row) {
            $countries[] = $row;
        }
        // Recupera lista stati
        $states = [];
        $stateRes = \Db::getInstance()->executeS('SELECT id_state, name, id_country FROM `'._DB_PREFIX_.'state` ORDER BY name');
        foreach ($stateRes as $row) {
            $states[] = $row;
        }

        if ($request->isMethod('POST')) {
            // Indirizzo
            $address1 = trim($request->request->get('address1'));
            $postcode = trim($request->request->get('postcode'));
            $city = trim($request->request->get('city'));
            $id_country = (int) $request->request->get('id_country');
            $id_state = (int) $request->request->get('id_state');
            $name = trim($request->request->get('name'));
            $location = trim($request->request->get('location'));
            $deleted = (int) $request->request->get('deleted');

            // Validazione
            if (!$name || !$location) {
                $message = 'Tutti i campi obbligatori del magazzino devono essere compilati.';
                $message_type = 'danger';
            } else {
                $result = \Db::getInstance()->update(
                    'warehouse',
                    [
                        'name' => pSQL($name),
                        'reference' => pSQL($location), // salva in reference
                        'deleted' => (int) $deleted,
                    ],
                    'id_warehouse = '.(int) $id
                );
                if ($result) {
                    // Gestione indirizzo (solo se almeno uno dei campi indirizzo è compilato)
                    if ($address1 || $postcode || $city) {
                        if ($address) {
                            // Update
                            \Db::getInstance()->update('address', [
                                'address1' => pSQL($address1),
                                'postcode' => pSQL($postcode),
                                'city' => pSQL($city),
                                'id_country' => (int) $id_country,
                                'id_state' => (int) $id_state,
                            ], 'id_address = '.(int) $address['id_address']);
                        } else {
                            // Insert
                            \Db::getInstance()->insert('address', [
                                'address1' => pSQL($address1),
                                'postcode' => pSQL($postcode),
                                'city' => pSQL($city),
                                'id_country' => (int) $id_country,
                                'id_state' => (int) $id_state,
                                'id_warehouse' => (int) $id,
                                'alias' => 'Warehouse',
                                'lastname' => 'Warehouse',
                                'firstname' => '',
                                'date_add' => date('Y-m-d H:i:s'),
                                'date_upd' => date('Y-m-d H:i:s'),
                                'deleted' => 0,
                            ]);
                        }
                    }
                    $this->addFlash('success', 'Magazzino aggiornato con successo.');

                    return $this->redirectToRoute('mpstockadv_admin_warehouse');
                } else {
                    $message = 'Errore durante l\'aggiornamento del magazzino.';
                    $message_type = 'danger';
                }
            }
            // Aggiorna i dati del magazzino per mostrarli nel form in caso di errore
            $warehouse['name'] = $name;
            $warehouse['reference'] = $location;
            $warehouse['deleted'] = $deleted;
        }

        return $this->render('@Modules/mpstockadv/views/PrestaShop/Admin/mpstockadv/warehouse_edit.html.twig', [
            'warehouse' => $warehouse,
            'address' => $address,
            'countries' => $countries,
            'states' => $states,
            'message' => $message,
            'message_type' => $message_type,
        ]);
    }
}
