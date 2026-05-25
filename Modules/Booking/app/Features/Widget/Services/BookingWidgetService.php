<?php

namespace Modules\Booking\app\Features\Widget\Services;

use Modules\Booking\app\Features\Widget\Models\BookingWidget;
use Modules\Booking\app\Features\Widget\Models\BookingWidgetDomain;
use Illuminate\Support\Str;

class BookingWidgetService
{
    public function createWidget(int $tenantId, array $data): BookingWidget
    {
        $widget = BookingWidget::create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'type' => $data['type'] ?? 'inline',
            'primary_color' => $data['primary_color'] ?? '#000000',
            'button_text' => $data['button_text'] ?? 'Book Now',
        ]);

        if (isset($data['domains']) && is_array($data['domains'])) {
            foreach ($data['domains'] as $domain) {
                // strip protocols
                $domainStr = preg_replace('/^https?:\/\//', '', $domain);
                $domainStr = preg_replace('/^www\./', '', $domainStr);
                $domainStr = trim($domainStr, '/');

                BookingWidgetDomain::create([
                    'tenant_id' => $tenantId,
                    'widget_id' => $widget->id,
                    'domain' => $domainStr,
                ]);
            }
        }

        return $widget;
    }
}
