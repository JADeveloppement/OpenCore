<?php

/**
 * Nom du Fichier : Controllers.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades\Controllers;

use Config\Facades\Http\Response;

abstract class Controllers
{
    protected \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        if ($pdo != null)
            $this->pdo = $pdo;
    }

    protected function render(string $viewName, array $data = [])
    {
        extract($data);

        ob_start();

        if (!defined('UNIT_TESTING')) {
            include dirname(__DIR__, 3) . "/resources/Views/{$viewName}.php";
        }

        return ob_get_clean();
    }

    /**
     * Put a header('Location : /$redirectlink') in the header of the request
     * @param string $redirectLink : link to be redirected to, don't use /
     * @return never
     */
    protected function redirect(string $redirectLink): void
    {
        Response::redirect($redirectLink);
    }

    protected function response(string $r = "1", bool $success = true, mixed $datas = [], string $message = "", int $responseHTTP = 200)
    {
        http_response_code($responseHTTP);

        return json_encode([
            "r" => $r,
            "success" => $success,
            "datas" => $datas,
            "message" => $message
        ]);
    }

    protected function makeDownload(string $fileName)
    {
        $filePath = dirname(__DIR__, 3) . "/html/files/$fileName";

        if (file_exists($filePath)) {

            if (ob_get_level())
                ob_end_clean();

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));

            readfile($filePath);
            exit;
        } else {
            return $this->response("0", false, [], "Fichier introuvable sur le serveur", 404);
        }
    }
}
