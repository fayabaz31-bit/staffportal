<?php

namespace App\Support;

use App\DutyCatalog;
use App\Models\CorrespondenceCategory;
use App\Models\CorrespondenceTemplate;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowStep;

/**
 * Seeded, configurable defaults for Registry correspondence.
 *
 * These are safe defaults for the pilot, not a declaration of NfLV's final
 * approval policy. Administrators can change steps and publish a new version;
 * documents already in progress keep the version they started on.
 */
final class RegistryWorkflowCatalog
{
    public const string ROUTINE = 'registry.correspondence.routine';

    public const string REGISTRAR = 'registry.correspondence.registrar';

    public const string DIRECTOR = 'registry.correspondence.director';

    /**
     * @return array<string, array{name: string, description: string, requires_registrar_approval: bool, requires_director_approval: bool, sort_order: int}>
     */
    public static function categories(): array
    {
        return [
            'annual_increment' => [
                'name' => 'Annual increment letters',
                'description' => 'Preparation and issuance of annual increment correspondence.',
                'requires_registrar_approval' => true,
                'requires_director_approval' => false,
                'sort_order' => 10,
            ],
            'confirmation' => [
                'name' => 'Confirmation letters',
                'description' => 'Confirmation of appointment correspondence.',
                'requires_registrar_approval' => true,
                'requires_director_approval' => false,
                'sort_order' => 20,
            ],
            'promotion' => [
                'name' => 'Promotion correspondence',
                'description' => 'Promotion-related personnel correspondence.',
                'requires_registrar_approval' => true,
                'requires_director_approval' => true,
                'sort_order' => 30,
            ],
            'leave_approval' => [
                'name' => 'Leave approval letters',
                'description' => 'Formal notification of an approved leave application.',
                'requires_registrar_approval' => false,
                'requires_director_approval' => false,
                'sort_order' => 40,
            ],
            'profile_correction' => [
                'name' => 'Staff profile correction correspondence',
                'description' => 'Correspondence confirming an approved correction to a staff record.',
                'requires_registrar_approval' => true,
                'requires_director_approval' => false,
                'sort_order' => 50,
            ],
            'internal_memo' => [
                'name' => 'Internal Registry memoranda',
                'description' => 'Internal Registry communication.',
                'requires_registrar_approval' => false,
                'requires_director_approval' => false,
                'sort_order' => 60,
            ],
            'personnel_notice' => [
                'name' => 'Personnel notices',
                'description' => 'General personnel-related notices.',
                'requires_registrar_approval' => true,
                'requires_director_approval' => false,
                'sort_order' => 70,
            ],
            'attestation' => [
                'name' => 'Attestation and service letters',
                'description' => 'Attestation of service or employment letters.',
                'requires_registrar_approval' => true,
                'requires_director_approval' => false,
                'sort_order' => 80,
            ],
        ];
    }

    public static function definitionCodeFor(CorrespondenceCategory $category): string
    {
        if ($category->requires_director_approval) {
            return self::DIRECTOR;
        }

        if ($category->requires_registrar_approval) {
            return self::REGISTRAR;
        }

        return self::ROUTINE;
    }

    /**
     * Seed or update categories, templates and the three default workflows.
     *
     * Existing published versions are left untouched so live documents keep
     * their chain; a new version is only added when the step plan itself has
     * not been created yet.
     */
    public static function sync(): void
    {
        foreach (self::categories() as $code => $definition) {
            CorrespondenceCategory::query()->updateOrCreate(
                ['code' => $code],
                $definition + ['is_active' => true],
            );
        }

        self::syncTemplate(
            'annual_increment',
            'Annual increment letter',
            'Annual Increment for {{ year }}',
            '<p>Dear {{ staff_name }},</p><p>This is to confirm that your annual increment for {{ year }} has been approved.</p><p>{{ body }}</p>',
        );

        self::syncTemplate(
            'profile_correction',
            'Profile correction confirmation',
            'Confirmation of staff record correction',
            '<p>Dear {{ staff_name }},</p><p>This confirms that the following correction has been made to your official staff record: {{ body }}</p>',
        );

        self::syncTemplate(
            'attestation',
            'Attestation of service',
            'Attestation of service',
            '<p>This is to attest that {{ staff_name }} ({{ staff_number }}) is a member of staff of the Nigeria French Language Village.</p><p>{{ body }}</p>',
        );

        self::defineWorkflow(
            self::ROUTINE,
            'Routine correspondence (Head of HR approval)',
            'Approval by the Head of HR, who may issue routine Registry correspondence within delegated authority.',
            [
                [
                    'code' => 'head_of_hr_approve',
                    'name' => 'Head of HR approval',
                    'action' => WorkflowStep::ACTION_APPROVE,
                    'duty' => DutyCatalog::REGISTRY_HEAD_REVIEW,
                ],
            ],
        );

        self::defineWorkflow(
            self::REGISTRAR,
            'Registry correspondence (Registrar approval)',
            'Head of HR recommends and the Registrar gives final Registry approval.',
            [
                [
                    'code' => 'head_of_hr_recommend',
                    'name' => 'Head of HR review',
                    'action' => WorkflowStep::ACTION_RECOMMEND,
                    'duty' => DutyCatalog::REGISTRY_HEAD_REVIEW,
                ],
                [
                    'code' => 'registrar_approve',
                    'name' => 'Registrar approval',
                    'action' => WorkflowStep::ACTION_APPROVE,
                    'duty' => DutyCatalog::REGISTRY_REGISTRAR_APPROVE,
                ],
            ],
        );

        self::defineWorkflow(
            self::DIRECTOR,
            'Reserved matter (Director sign-off)',
            'Registry review, Registrar recommendation, then Director/CEO decision. Reserved matters cannot be issued before the executive decision.',
            [
                [
                    'code' => 'head_of_hr_review',
                    'name' => 'Head of HR review',
                    'action' => WorkflowStep::ACTION_REVIEW,
                    'duty' => DutyCatalog::REGISTRY_HEAD_REVIEW,
                ],
                [
                    'code' => 'registrar_recommend',
                    'name' => 'Registrar recommendation',
                    'action' => WorkflowStep::ACTION_RECOMMEND,
                    'duty' => DutyCatalog::REGISTRY_REGISTRAR_APPROVE,
                ],
                [
                    'code' => 'director_approve',
                    'name' => 'Director / CEO decision',
                    'action' => WorkflowStep::ACTION_APPROVE,
                    'duty' => DutyCatalog::REGISTRY_DIRECTOR_ESCALATION,
                ],
            ],
        );
    }

    /**
     * @param  list<array{code: string, name: string, action: string, duty: string}>  $steps
     */
    private static function defineWorkflow(string $code, string $name, string $description, array $steps): void
    {
        $definition = WorkflowDefinition::query()->firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'document_type' => 'correspondence',
                'description' => $description,
                'is_active' => true,
            ],
        );

        $version = $definition->versions()->orderByDesc('version')->first();

        if ($version !== null && $version->steps()->exists()) {
            return;
        }

        $version = $definition->versions()->create([
            'version' => ($version?->version ?? 0) + 1,
            'is_published' => true,
            'effective_from' => now()->toDateString(),
        ]);

        foreach ($steps as $index => $step) {
            $version->steps()->create([
                'sequence' => $index + 1,
                'code' => $step['code'],
                'name' => $step['name'],
                'action_required' => $step['action'],
                'approver_type' => WorkflowStep::APPROVER_DUTY,
                'approver_duty' => $step['duty'],
                'is_mandatory' => true,
            ]);
        }
    }

    private static function syncTemplate(string $categoryCode, string $name, string $subject, string $body): void
    {
        $category = CorrespondenceCategory::query()->where('code', $categoryCode)->first();

        if ($category === null) {
            return;
        }

        CorrespondenceTemplate::query()->updateOrCreate(
            ['correspondence_category_id' => $category->id, 'name' => $name],
            [
                'subject_template' => $subject,
                'body_template' => $body,
                'is_active' => true,
            ],
        );
    }
}
