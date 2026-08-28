<?php
/**
 * IXEA OS - Objeto de Transferencia / Clase App
 * /src/app/classes/App.php
 */

namespace App\Classes;

use App\Models\App as AppModel;

class App {
    private int $id;
    private string $code;
    private string $title;
    private string $url;
    private string $permissionKey;
    private string $icon;
    private string $colorHex;
    private string $colorName;

    public function __construct(array $data) {
        $this->id = (int)($data['id'] ?? 0);
        $this->code = $data['code'] ?? '';
        $this->title = $data['title'] ?? '';
        $this->url = '/' . ($data['code'] ?? '');
        $this->permissionKey = $data['permission_key'] ?? '';
        $this->icon = $data['icon'] ?? '';
        $this->colorHex = $data['color_hex'] ?? '#000000';
        $this->colorName = $data['color_name'] ?? 'default';
    }

    public function getId(): int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function getTitle(): string { return $this->title; }
    public function getUrl(): string { return $this->url; }
    public function getPermissionKey(): string { return $this->permissionKey; }
    public function getIcon(): string { return $this->icon; }
    public function getColorHex(): string { return $this->colorHex; }
    public function getColorName(): string { return $this->colorName; }

    /**
     * Obtiene las aplicaciones accesibles usando el Modelo Eloquent (igual que en Login)
     */
    public static function getAccessibleApps(bool $isSuper, array $userPermissions): array {
        // Carga de la BD usando Eloquent con Eager Loading
        $records = AppModel::with(['permission', 'color'])
            ->where('status_id', 1)
            ->orderBy('title', 'asc')
            ->get();

        $accessibleApps = [];

        foreach ($records as $record) {
            $permissionKey = $record->permission->key ?? '';

            if ($isSuper || in_array($permissionKey, $userPermissions, true)) {
                $accessibleApps[] = new self([
                    'id'             => $record->id,
                    'code'           => $record->code,
                    'title'          => $record->title,
                    'permission_key' => $permissionKey,
                    'icon'           => $record->icon,
                    'color_hex'      => $record->color->hex ?? '#000000',
                    'color_name'     => $record->color->name ?? 'black'
                ]);
            }
        }

        return $accessibleApps;
    }
}