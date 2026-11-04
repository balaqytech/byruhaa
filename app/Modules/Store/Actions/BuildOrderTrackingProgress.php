<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Models\Order;

class BuildOrderTrackingProgress
{
    /**
     * @return array{steps: array<int, array{status: string, label: string, icon: string, reached: bool, current: bool}>, completed_segments: int, total_segments: int, status_label: string, message: string, remaining: string, is_exception: bool, is_refund: bool, refund_completed: bool}
     */
    public function execute(Order $order): array
    {
        $status = $order->status->getValue();
        $steps = [
            ['status' => 'pending_payment', 'icon' => 'wallet-02'],
            ['status' => 'confirmed', 'icon' => 'checkmark-circle-02'],
            ['status' => 'preparing', 'icon' => 'sparkles'],
            ['status' => 'ready_for_pickup', 'icon' => 'ticket-01'],
            ['status' => 'completed', 'icon' => 'checkmark-badge-01'],
        ];

        if ($order->pickup_type === 'scheduled'
            || $status === 'accepted'
            || $order->statusHistory->contains('to_status', 'accepted')) {
            array_splice($steps, 2, 0, [['status' => 'accepted', 'icon' => 'check-list']]);
        }

        $isException = in_array($status, ['rejected', 'cancelled', 'expired', 'refund_pending', 'refunded'], true);
        $operationalStatus = $this->operationalStatus($order, $status);
        $currentIndex = array_search($operationalStatus, array_column($steps, 'status'), true);
        $currentIndex = $currentIndex === false ? 0 : $currentIndex;
        $totalSegments = count($steps) - 1;

        $steps = array_map(
            fn (array $step, int $index): array => [
                ...$step,
                'label' => __('admin.store.order_statuses.'.$step['status']),
                'reached' => $index <= $currentIndex,
                'current' => ! $isException && $index === $currentIndex,
            ],
            $steps,
            array_keys($steps),
        );

        return [
            'steps' => $steps,
            'completed_segments' => $currentIndex,
            'total_segments' => $totalSegments,
            'status_label' => __('admin.store.order_statuses.'.$status),
            'message' => $this->message($status),
            'remaining' => $this->remaining($status, array_column(array_slice($steps, $currentIndex + 1), 'label')),
            'is_exception' => $isException,
            'is_refund' => in_array($status, ['refund_pending', 'refunded'], true),
            'refund_completed' => $status === 'refunded',
        ];
    }

    private function operationalStatus(Order $order, string $status): string
    {
        if (in_array($status, ['refund_pending', 'refunded'], true)) {
            return $order->statusHistory->firstWhere('to_status', 'refund_pending')?->from_status ?? 'pending_payment';
        }

        if (in_array($status, ['rejected', 'cancelled'], true)) {
            return $order->statusHistory->firstWhere('to_status', $status)?->from_status ?? 'pending_payment';
        }

        return $status === 'expired' ? 'pending_payment' : $status;
    }

    private function message(string $status): string
    {
        return match ($status) {
            'pending_payment' => 'لم يكتمل الدفع بعد. يبدأ تجهيز الطلب بعد تأكيده.',
            'confirmed' => 'تم تأكيد الطلب، وسينتقل إلى فريق القهوة.',
            'accepted' => 'قُبل الطلب المجدول، وننتظر بدء التجهيز.',
            'preparing' => 'يعمل فريق القهوة على تجهيز طلبك الآن.',
            'ready_for_pickup' => 'طلبك جاهز ويمكنك استلامه.',
            'completed' => 'اكتملت رحلة الطلب. شكرًا لاختيارك قهوة بيرحاء.',
            'rejected' => 'تعذر قبول الطلب. تواصل معنا إذا احتجت إلى المساعدة.',
            'cancelled' => 'أُلغي الطلب ولن يستمر إلى مرحلة الاستلام.',
            'expired' => 'انتهت مهلة الدفع، فتوقف الطلب قبل التجهيز.',
            'refund_pending' => 'الاسترداد قيد المعالجة. سنحدّث الحالة بعد اكتماله.',
            'refunded' => 'اكتملت عملية الاسترداد.',
            default => 'تابع حالة طلبك هنا.',
        };
    }

    /** @param array<int, string> $upcomingSteps */
    private function remaining(string $status, array $upcomingSteps): string
    {
        if ($status === 'refund_pending') {
            return 'المتبقي: اكتمال عملية الاسترداد.';
        }

        if (in_array($status, ['rejected', 'cancelled', 'expired', 'refunded'], true)) {
            return 'لا توجد خطوات استلام متبقية لهذا الطلب.';
        }

        if ($upcomingSteps === []) {
            return 'لا توجد خطوات متبقية؛ اكتمل الطلب.';
        }

        $nextStep = array_shift($upcomingSteps);

        return 'الخطوة التالية: '.$nextStep.'.'.($upcomingSteps === []
            ? ''
            : ' ثم: '.implode('، ', $upcomingSteps).'.');
    }
}
