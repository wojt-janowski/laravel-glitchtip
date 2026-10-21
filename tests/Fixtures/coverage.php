<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| API coverage manifest
|--------------------------------------------------------------------------
|
| Every operation in tests/Fixtures/openapi.json, keyed by operationId.
| A closure calls the client; CoverageTest runs it against a fake and checks
| the request matches the operation's method and path. An alias names the
| operation that serves the same purpose through another path. An exclusion
| says why the client deliberately leaves an operation out.
|
*/

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Data\Alert;
use WojtJanowski\GlitchTip\Data\AlertInput;
use WojtJanowski\GlitchTip\Data\AssembleStatus;
use WojtJanowski\GlitchTip\Data\BillingSubscription;
use WojtJanowski\GlitchTip\Data\BillingUsage;
use WojtJanowski\GlitchTip\Data\ChunkedFile;
use WojtJanowski\GlitchTip\Data\ChunkUploadOptions;
use WojtJanowski\GlitchTip\Data\Comment;
use WojtJanowski\GlitchTip\Data\Commit;
use WojtJanowski\GlitchTip\Data\CurrentAuth;
use WojtJanowski\GlitchTip\Data\Deploy;
use WojtJanowski\GlitchTip\Data\Event;
use WojtJanowski\GlitchTip\Data\InstanceLicense;
use WojtJanowski\GlitchTip\Data\Invitation;
use WojtJanowski\GlitchTip\Data\Issue;
use WojtJanowski\GlitchTip\Data\IssueFilter;
use WojtJanowski\GlitchTip\Data\IssueUpdate;
use WojtJanowski\GlitchTip\Data\LogEntry;
use WojtJanowski\GlitchTip\Data\LogStats;
use WojtJanowski\GlitchTip\Data\Member;
use WojtJanowski\GlitchTip\Data\Monitor;
use WojtJanowski\GlitchTip\Data\MonitorCheck;
use WojtJanowski\GlitchTip\Data\MonitorInput;
use WojtJanowski\GlitchTip\Data\Organization;
use WojtJanowski\GlitchTip\Data\OrganizationSocialApp;
use WojtJanowski\GlitchTip\Data\Overage;
use WojtJanowski\GlitchTip\Data\Project;
use WojtJanowski\GlitchTip\Data\ProjectEnvironment;
use WojtJanowski\GlitchTip\Data\ProjectInput;
use WojtJanowski\GlitchTip\Data\ProjectKey;
use WojtJanowski\GlitchTip\Data\Release;
use WojtJanowski\GlitchTip\Data\ReleaseFile;
use WojtJanowski\GlitchTip\Data\Repository;
use WojtJanowski\GlitchTip\Data\ServerInfo;
use WojtJanowski\GlitchTip\Data\StatsSeries;
use WojtJanowski\GlitchTip\Data\StatusPage;
use WojtJanowski\GlitchTip\Data\Team;
use WojtJanowski\GlitchTip\Data\TransactionGroup;
use WojtJanowski\GlitchTip\Data\User;
use WojtJanowski\GlitchTip\Data\UserEmail;
use WojtJanowski\GlitchTip\Data\UserOptions;
use WojtJanowski\GlitchTip\Enums\MemberRole;
use WojtJanowski\GlitchTip\GlitchTip;
use WojtJanowski\GlitchTip\Support\CursorPage;

$file = new ChunkedFile(checksum: str_repeat('a', 40), chunks: [str_repeat('b', 40)], size: 3);
$start = CarbonImmutable::parse('2026-10-01T00:00:00Z');
$end = CarbonImmutable::parse('2026-10-02T00:00:00Z');

return [
    // Instance
    'glitchtip_api_api_get_settings' => fn (GlitchTip $g): ServerInfo => $g->instance()->settings(),
    'glitchtip_api_api_get_organization_login_settings' => fn (GlitchTip $g): array => $g->instance()->loginProviders(),
    'glitchtip_api_api_get_instance_license' => fn (GlitchTip $g): InstanceLicense => $g->instance()->license(),
    'glitchtip_api_api_get_support_link' => fn (GlitchTip $g): string => $g->instance()->supportLink(),
    'glitchtip_api_api_api_root' => fn (GlitchTip $g): CurrentAuth => $g->instance()->whoami(),

    // API tokens
    'apps_api_tokens_api_list_api_tokens' => ['excluded' => 'Session (browser login) authentication only; API tokens cannot call it.'],
    'apps_api_tokens_api_create_api_token' => ['excluded' => 'Session (browser login) authentication only; API tokens cannot call it.'],
    'apps_api_tokens_api_delete_api_token' => ['excluded' => 'Session (browser login) authentication only; API tokens cannot call it.'],

    // Ingest and the browser feedback widget
    'apps_event_ingest_api_event_store' => ['excluded' => 'Event ingest, authenticated by a project DSN; send events with a Sentry SDK.'],
    'apps_event_ingest_api_event_security' => ['excluded' => 'CSP report ingest, posted by browsers to a DSN URL.'],
    'apps_event_ingest_embed_api_get_embed_error_page' => ['excluded' => 'Serves the browser crash-report dialog script for Sentry SDKs, keyed by DSN.'],
    'apps_event_ingest_embed_api_submit_embed_error_page' => ['excluded' => 'Form post from the browser crash-report dialog, keyed by DSN.'],

    // Alerts
    'apps_alerts_api_list_project_alerts' => fn (GlitchTip $g): CursorPage => $g->alerts()->list('backend'),
    'apps_alerts_api_create_project_alert' => fn (GlitchTip $g): Alert => $g->alerts()->create('backend', new AlertInput(name: 'Spike')),
    'apps_alerts_api_update_project_alert' => fn (GlitchTip $g): Alert => $g->alerts()->update('backend', 3, new AlertInput(name: 'Spike')),
    'apps_alerts_api_delete_project_alert' => fn (GlitchTip $g) => $g->alerts()->delete('backend', 3),
    'apps_alerts_api_test_project_alert' => fn (GlitchTip $g): array => $g->alerts()->test('backend', 3),

    // Debug files and chunked uploads
    'apps_difs_api_difs_assemble_api' => fn (GlitchTip $g): AssembleStatus => $g->uploads()->assembleDebugFile('backend', $file, 'app.dSYM'),
    'apps_difs_api_project_reprocessing' => ['excluded' => 'A documented no-op that exists only so sentry-cli upload-dif succeeds.'],
    'apps_difs_api_list_dsyms' => fn (GlitchTip $g): CursorPage => $g->uploads()->debugFiles('backend'),
    'apps_difs_api_dsyms' => fn (GlitchTip $g): array => $g->uploads()->uploadProguardMappings('backend', 'PK'),
    'apps_files_api_get_chunk_upload_info' => fn (GlitchTip $g): ChunkUploadOptions => $g->uploads()->options(),
    'apps_files_api_chunk_upload' => fn (GlitchTip $g): ChunkedFile => $g->uploads()->upload('abc'),
    'apps_sourcecode_api_artifact_bundle_assemble' => fn (GlitchTip $g): AssembleStatus => $g->uploads()->assembleArtifactBundle($file, ['backend'], '1.4.0'),

    // Environments
    'apps_environments_api_list_environments' => fn (GlitchTip $g): CursorPage => $g->environments()->list(),
    'apps_environments_api_list_environment_projects' => fn (GlitchTip $g): CursorPage => $g->environments()->forProject('backend'),
    'apps_environments_api_update_environment_project' => fn (GlitchTip $g): ProjectEnvironment => $g->environments()->hide('backend', 'staging'),

    // Import
    'apps_importer_api_importer' => fn (GlitchTip $g) => $g->organizations()->import('https://sentry.example.com', 'source-token'),

    // Comments
    'apps_issue_events_api_comments_list_comments' => fn (GlitchTip $g): CursorPage => $g->comments()->list(42),
    'apps_issue_events_api_comments_add_comment' => fn (GlitchTip $g): Comment => $g->comments()->create(42, 'On it'),
    'apps_issue_events_api_comments_update_comment' => fn (GlitchTip $g): Comment => $g->comments()->update(42, 7, 'Fixed'),
    'apps_issue_events_api_comments_delete_comment' => fn (GlitchTip $g) => $g->comments()->delete(42, 7),
    'apps_issue_events_api_comments_list_organization_comments' => ['alias' => 'apps_issue_events_api_comments_list_comments'],
    'apps_issue_events_api_comments_add_organization_comment' => ['alias' => 'apps_issue_events_api_comments_add_comment'],
    'apps_issue_events_api_comments_update_organization_comment' => ['alias' => 'apps_issue_events_api_comments_update_comment'],
    'apps_issue_events_api_comments_delete_organization_comment' => ['alias' => 'apps_issue_events_api_comments_delete_comment'],

    // Events
    'apps_issue_events_api_events_list_issue_event' => fn (GlitchTip $g): CursorPage => $g->events()->forIssue(42),
    'apps_issue_events_api_events_get_latest_issue_event' => fn (GlitchTip $g): Event => $g->events()->latest(42),
    'apps_issue_events_api_events_get_issue_event' => fn (GlitchTip $g): Event => $g->events()->get(42, 'abc123'),
    'apps_issue_events_api_events_list_project_issue_event' => fn (GlitchTip $g): CursorPage => $g->events()->forProject('backend'),
    'apps_issue_events_api_events_get_project_issue_event' => fn (GlitchTip $g): Event => $g->events()->getForProject('backend', 'abc123'),
    'apps_issue_events_api_events_get_event_json' => fn (GlitchTip $g): array => $g->events()->raw(42, 'abc123'),
    'apps_issue_events_api_events_list_organization_issue_event' => ['alias' => 'apps_issue_events_api_events_list_issue_event'],
    'apps_issue_events_api_events_get_organization_latest_issue_event' => ['alias' => 'apps_issue_events_api_events_get_latest_issue_event'],
    'apps_issue_events_api_events_get_organization_issue_event' => ['alias' => 'apps_issue_events_api_events_get_issue_event'],

    // Issues
    'apps_issue_events_api_issues_get_issue' => fn (GlitchTip $g): Issue => $g->issues()->get(42),
    'apps_issue_events_api_issues_update_issue' => fn (GlitchTip $g): Issue => $g->issues()->resolve(42),
    'apps_issue_events_api_issues_delete_issue' => fn (GlitchTip $g) => $g->issues()->delete(42),
    'apps_issue_events_api_issues_list_issue_commits' => fn (GlitchTip $g): array => $g->issues()->commits(42),
    'apps_issue_events_api_issues_get_issue_by_short_id' => fn (GlitchTip $g): Issue => $g->issues()->findByShortId('BACKEND-1A'),
    'apps_issue_events_api_issues_list_issues' => fn (GlitchTip $g): CursorPage => $g->issues()->list(),
    'apps_issue_events_api_issues_delete_issues' => fn (GlitchTip $g) => $g->issues()->bulkDelete(IssueFilter::ids([42])),
    'apps_issue_events_api_issues_update_issues' => fn (GlitchTip $g) => $g->issues()->bulkUpdate(IssueFilter::ids([42]), IssueUpdate::ignored()),
    'apps_issue_events_api_issues_update_project_issues' => fn (GlitchTip $g) => $g->issues()->bulkUpdateForProject('backend', IssueFilter::ids([42]), IssueUpdate::ignored()),
    'apps_issue_events_api_issues_list_project_issues' => fn (GlitchTip $g): CursorPage => $g->issues()->forProject('backend'),
    'apps_issue_events_api_issues_list_issue_tags' => fn (GlitchTip $g): array => $g->issues()->tags(42),
    'apps_issue_events_api_issues_issue_stats' => fn (GlitchTip $g): array => $g->issues()->stats([42, 43]),
    'apps_issue_events_api_user_reports_list_user_reports' => fn (GlitchTip $g): CursorPage => $g->issues()->userReports(42),
    'apps_issue_events_api_hashes_list_issue_hashes' => fn (GlitchTip $g): CursorPage => $g->issues()->hashes(42),
    'apps_issue_events_api_hashes_delete_hash' => fn (GlitchTip $g) => $g->issues()->deleteHashes(42, ['0b6e5d0c-0000-4000-8000-000000000001']),
    'apps_issue_events_api_issues_get_organization_issue' => ['alias' => 'apps_issue_events_api_issues_get_issue'],
    'apps_issue_events_api_issues_delete_organization_issue' => ['alias' => 'apps_issue_events_api_issues_delete_issue'],
    'apps_issue_events_api_issues_update_organization_issue' => ['alias' => 'apps_issue_events_api_issues_update_issue'],
    'apps_issue_events_api_issues_list_organization_issue_commits' => ['alias' => 'apps_issue_events_api_issues_list_issue_commits'],
    'apps_issue_events_api_issues_list_organization_issue_tags' => ['alias' => 'apps_issue_events_api_issues_list_issue_tags'],
    'apps_issue_events_api_user_reports_list_organization_user_reports' => ['alias' => 'apps_issue_events_api_user_reports_list_user_reports'],

    // Logs
    'apps_logs_api_list_logs' => fn (GlitchTip $g): CursorPage => $g->logs()->list(),
    'apps_logs_api_get_log' => fn (GlitchTip $g): LogEntry => $g->logs()->get('0b6e5d0c-0000-4000-8000-000000000001'),
    'apps_logs_api_get_log_stats' => fn (GlitchTip $g): LogStats => $g->logs()->stats(),
    'apps_logs_api_list_log_resources' => fn (GlitchTip $g): array => $g->logs()->resources(),

    // Organizations
    'apps_organizations_ext_api_list_organizations' => fn (GlitchTip $g): CursorPage => $g->organizations()->list(),
    'apps_organizations_ext_api_create_organization' => fn (GlitchTip $g): Organization => $g->organizations()->create('Acme'),
    'apps_organizations_ext_api_get_organization' => fn (GlitchTip $g): Organization => $g->organizations()->get(),
    'apps_organizations_ext_api_update_organization' => fn (GlitchTip $g): Organization => $g->organizations()->update('Acme Inc'),
    'apps_organizations_ext_api_delete_organization' => fn (GlitchTip $g) => $g->organizations()->delete(),

    // Members
    'apps_organizations_ext_api_list_organization_members' => fn (GlitchTip $g): CursorPage => $g->members()->list(),
    'apps_organizations_ext_api_create_organization_member' => fn (GlitchTip $g): Member => $g->members()->invite('new@example.com'),
    'apps_organizations_ext_api_list_team_organization_members' => fn (GlitchTip $g): CursorPage => $g->members()->forTeam('backend'),
    'apps_organizations_ext_api_get_organization_member' => fn (GlitchTip $g): Member => $g->members()->get(5),
    'apps_organizations_ext_api_delete_organization_member' => fn (GlitchTip $g) => $g->members()->delete(5),
    'apps_organizations_ext_api_update_organization_member' => fn (GlitchTip $g): Member => $g->members()->update(5, MemberRole::Admin),
    'apps_organizations_ext_api_set_organization_owner' => fn (GlitchTip $g): Member => $g->members()->makeOwner(5),
    'apps_organizations_ext_api_get_accept_invite' => fn (GlitchTip $g): Invitation => $g->members()->invitation(5, 'invite-token'),
    'apps_organizations_ext_api_accept_invite' => fn (GlitchTip $g): Invitation => $g->members()->acceptInvitation(5, 'invite-token'),

    // Social apps
    'apps_organizations_ext_social_app_api_list_organization_social_apps' => fn (GlitchTip $g): array => $g->socialApps()->list(),
    'apps_organizations_ext_social_app_api_create_organization_social_app' => fn (GlitchTip $g): OrganizationSocialApp => $g->socialApps()->create('Okta', 'client', 'secret', serverUrl: 'https://okta.example.com'),
    'apps_organizations_ext_social_app_api_update_organization_social_app' => fn (GlitchTip $g): OrganizationSocialApp => $g->socialApps()->update(2, 'Okta', 'client'),
    'apps_organizations_ext_social_app_api_delete_organization_social_app' => fn (GlitchTip $g) => $g->socialApps()->delete(2),

    // Performance
    'apps_performance_api_list_transaction_groups' => fn (GlitchTip $g): CursorPage => $g->performance()->transactionGroups(),
    'apps_performance_api_get_transaction_group' => fn (GlitchTip $g): TransactionGroup => $g->performance()->transactionGroup(9),
    'apps_performance_api_list_transaction_spans' => fn (GlitchTip $g): array => $g->performance()->transactionSpans(9),
    'apps_performance_api_list_span_groups' => fn (GlitchTip $g): array => $g->performance()->spanGroups(),
    'apps_performance_api_list_n_plus_one_patterns' => fn (GlitchTip $g): array => $g->performance()->nPlusOne(),
    'apps_performance_api_get_transaction_trend' => fn (GlitchTip $g): array => $g->performance()->trend(9),

    // Projects
    'apps_projects_api_list_projects' => fn (GlitchTip $g): CursorPage => $g->projects()->accessible(),
    'apps_projects_api_get_project' => fn (GlitchTip $g): Project => $g->projects()->get('backend'),
    'apps_projects_api_update_project' => fn (GlitchTip $g): Project => $g->projects()->update('backend', new ProjectInput(name: 'Backend')),
    'apps_projects_api_delete_project' => fn (GlitchTip $g) => $g->projects()->delete('backend'),
    'apps_projects_api_list_team_projects' => fn (GlitchTip $g): CursorPage => $g->projects()->forTeam('core'),
    'apps_projects_api_create_project' => fn (GlitchTip $g): Project => $g->projects()->create('core', new ProjectInput(name: 'Backend', platform: 'php-laravel')),
    'apps_projects_api_list_organization_projects' => fn (GlitchTip $g): CursorPage => $g->projects()->list(),
    'apps_projects_api_list_project_keys' => fn (GlitchTip $g): CursorPage => $g->projects()->keys('backend'),
    'apps_projects_api_create_project_key' => fn (GlitchTip $g): ProjectKey => $g->projects()->createKey('backend', 'CI'),
    'apps_projects_api_get_project_key' => fn (GlitchTip $g): ProjectKey => $g->projects()->key('backend', '0b6e5d0c-0000-4000-8000-000000000001'),
    'apps_projects_api_update_project_key' => fn (GlitchTip $g): ProjectKey => $g->projects()->updateKey('backend', '0b6e5d0c-0000-4000-8000-000000000001', 'CI'),
    'apps_projects_api_delete_project_key' => fn (GlitchTip $g) => $g->projects()->deleteKey('backend', '0b6e5d0c-0000-4000-8000-000000000001'),
    'apps_projects_api_user_notification_alerts' => fn (GlitchTip $g): array => $g->projects()->alertPreferences(),
    'apps_projects_api_update_user_notification_alerts' => fn (GlitchTip $g) => $g->projects()->updateAlertPreferences([7 => 1]),

    // Stats
    'apps_stats_api_stats_v2' => fn (GlitchTip $g): StatsSeries => $g->stats()->series($start, $end),

    // Billing
    'apps_stripe_api_list_stripe_products' => fn (GlitchTip $g): array => $g->billing()->products(),
    'apps_stripe_api_get_stripe_subscription' => fn (GlitchTip $g): ?\WojtJanowski\GlitchTip\Data\BillingSubscription => $g->billing()->subscription(),
    'apps_stripe_api_create_stripe_session' => fn (GlitchTip $g): string => $g->billing()->checkout('price_123'),
    'apps_stripe_api_stripe_billing_portal_session' => fn (GlitchTip $g): string => $g->billing()->portal(),
    'apps_stripe_api_stripe_create_subscription' => fn (GlitchTip $g): BillingSubscription => $g->billing()->subscribe('price_free'),
    'apps_stripe_api_subscription_events_count_for_period' => fn (GlitchTip $g): BillingUsage => $g->billing()->usage(),
    'apps_stripe_api_subscription_events_count_daily' => fn (GlitchTip $g): array => $g->billing()->dailyUsage(),
    'apps_stripe_api_get_overage_status' => fn (GlitchTip $g): Overage => $g->billing()->overage(),
    'apps_stripe_api_configure_overage' => fn (GlitchTip $g): Overage => $g->billing()->configureOverage(true, 5000),

    // Repositories
    'apps_sourcecode_api_list_repositories' => fn (GlitchTip $g): CursorPage => $g->repositories()->list(),
    'apps_sourcecode_api_create_repository' => fn (GlitchTip $g): Repository => $g->repositories()->create('acme/backend', 'https://github.com/acme/backend'),

    // Teams
    'apps_teams_api_get_team' => fn (GlitchTip $g): Team => $g->teams()->get('core'),
    'apps_teams_api_update_team' => fn (GlitchTip $g): Team => $g->teams()->update('core', 'platform'),
    'apps_teams_api_delete_team' => fn (GlitchTip $g) => $g->teams()->delete('core'),
    'apps_teams_api_list_teams' => fn (GlitchTip $g): CursorPage => $g->teams()->list(),
    'apps_teams_api_create_team' => fn (GlitchTip $g): Team => $g->teams()->create('core'),
    'apps_teams_api_add_member_to_team' => fn (GlitchTip $g): Team => $g->teams()->addMember('core', 5),
    'apps_teams_api_delete_member_from_team' => fn (GlitchTip $g): Team => $g->teams()->removeMember('core', 5),
    'apps_teams_api_list_project_teams' => fn (GlitchTip $g): CursorPage => $g->teams()->forProject('backend'),
    'apps_teams_api_add_team_to_project' => fn (GlitchTip $g): Project => $g->teams()->addToProject('core', 'backend'),
    'apps_teams_api_delete_team_from_project' => fn (GlitchTip $g): Project => $g->teams()->removeFromProject('core', 'backend'),

    // Uptime
    'apps_uptime_api_heartbeat_check' => fn (GlitchTip $g): MonitorCheck => $g->monitors()->heartbeat('0b6e5d0c-0000-4000-8000-000000000001'),
    'apps_uptime_api_list_monitors' => fn (GlitchTip $g): CursorPage => $g->monitors()->list(),
    'apps_uptime_api_create_monitor' => fn (GlitchTip $g): Monitor => $g->monitors()->create(new MonitorInput(name: 'Home', url: 'https://example.com')),
    'apps_uptime_api_get_monitor' => fn (GlitchTip $g): Monitor => $g->monitors()->get(5),
    'apps_uptime_api_update_monitor' => fn (GlitchTip $g): Monitor => $g->monitors()->update(5, new MonitorInput(name: 'Home', url: 'https://example.com')),
    'apps_uptime_api_delete_monitor' => fn (GlitchTip $g) => $g->monitors()->delete(5),
    'apps_uptime_api_list_monitor_checks' => fn (GlitchTip $g): CursorPage => $g->monitors()->checks(5),
    'apps_uptime_api_list_status_pages' => fn (GlitchTip $g): CursorPage => $g->monitors()->statusPages(),
    'apps_uptime_api_create_status_page' => fn (GlitchTip $g): StatusPage => $g->monitors()->createStatusPage('Status', public: true),

    // Users
    'apps_users_api_list_users' => fn (GlitchTip $g): CursorPage => $g->users()->list(),
    'apps_users_api_get_user' => fn (GlitchTip $g): User => $g->users()->get(),
    'apps_users_api_delete_user' => fn (GlitchTip $g) => $g->users()->delete(),
    'apps_users_api_update_user' => fn (GlitchTip $g): User => $g->users()->update(new UserOptions(timezone: 'Australia/Sydney'), 'Wojt'),
    'apps_users_api_list_emails' => fn (GlitchTip $g): array => $g->users()->emails(),
    'apps_users_api_create_email' => fn (GlitchTip $g): UserEmail => $g->users()->addEmail('second@example.com'),
    'apps_users_api_set_email_as_primary' => fn (GlitchTip $g): UserEmail => $g->users()->makeEmailPrimary('second@example.com'),
    'apps_users_api_delete_email' => fn (GlitchTip $g) => $g->users()->deleteEmail('second@example.com'),
    'apps_users_api_send_confirm_email' => fn (GlitchTip $g) => $g->users()->sendEmailConfirmation('second@example.com'),
    'apps_users_api_get_notifications' => fn (GlitchTip $g): bool => $g->users()->subscribedByDefault(),
    'apps_users_api_update_notifications' => fn (GlitchTip $g): bool => $g->users()->updateNotifications(false),
    'apps_users_api_generate_recovery_codes' => fn (GlitchTip $g): array => $g->users()->generateRecoveryCodes(),
    'apps_users_api_set_recovery_codes' => fn (GlitchTip $g) => $g->users()->saveRecoveryCodes('abcd-efgh'),

    // Setup wizard
    'apps_wizard_api_setup_wizard' => fn (GlitchTip $g): string => $g->wizard()->start(),
    'apps_wizard_api_setup_wizard_hash' => fn (GlitchTip $g): array => $g->wizard()->data('wizard-hash'),
    'apps_wizard_api_setup_wizard_delete' => fn (GlitchTip $g) => $g->wizard()->delete('wizard-hash'),
    'apps_wizard_api_setup_wizard_set_token' => fn (GlitchTip $g) => $g->wizard()->authorize('wizard-hash'),

    // Releases
    'apps_releases_api_create_release' => fn (GlitchTip $g): Release => $g->releases()->create('1.4.0', ['backend']),
    'apps_releases_api_list_releases' => fn (GlitchTip $g): CursorPage => $g->releases()->list(),
    'apps_releases_api_create_project_release' => fn (GlitchTip $g): Release => $g->releases()->createForProject('backend', '1.4.0'),
    'apps_releases_api_list_project_releases' => fn (GlitchTip $g): CursorPage => $g->releases()->list(project: 'backend'),
    'apps_releases_api_get_release' => fn (GlitchTip $g): Release => $g->releases()->get('1.4.0'),
    'apps_releases_api_update_release' => fn (GlitchTip $g): Release => $g->releases()->update('1.4.0', ref: 'abc123'),
    'apps_releases_api_delete_organization_release' => fn (GlitchTip $g) => $g->releases()->delete('1.4.0'),
    'apps_releases_api_list_release_files' => fn (GlitchTip $g): CursorPage => $g->releases()->files('1.4.0'),
    'apps_releases_api_delete_organization_release_file' => fn (GlitchTip $g) => $g->releases()->deleteFile('1.4.0', 11),
    'apps_releases_api_get_project_release' => fn (GlitchTip $g): Release => $g->releases()->get('1.4.0', project: 'backend'),
    'apps_releases_api_update_project_release' => fn (GlitchTip $g): Release => $g->releases()->update('1.4.0', ref: 'abc123', project: 'backend'),
    'apps_releases_api_delete_project_release' => fn (GlitchTip $g) => $g->releases()->delete('1.4.0', project: 'backend'),
    'apps_releases_api_list_project_release_files' => fn (GlitchTip $g): CursorPage => $g->releases()->files('1.4.0', project: 'backend'),
    'apps_releases_api_delete_project_release_file' => fn (GlitchTip $g) => $g->releases()->deleteFile('1.4.0', 11, project: 'backend'),
    'apps_releases_api_get_project_release_file' => fn (GlitchTip $g): ReleaseFile => $g->releases()->file('1.4.0', 11, 'backend'),
    'apps_releases_api_get_organization_release_file' => ['alias' => 'apps_releases_api_get_project_release_file'],
    'apps_releases_api_create_deploy' => fn (GlitchTip $g): Deploy => $g->releases()->createDeploy('1.4.0', 'production'),
    'apps_releases_api_list_deploys' => fn (GlitchTip $g): array => $g->releases()->deploys('1.4.0'),
    'apps_releases_api_create_commits' => fn (GlitchTip $g): Release => $g->releases()->setCommits('1.4.0', [new Commit(id: 'abc123', message: 'Fix')]),
    'apps_releases_api_list_commits' => fn (GlitchTip $g): array => $g->releases()->commits('1.4.0'),
    'apps_releases_api_assemble_release' => fn (GlitchTip $g): AssembleStatus => $g->releases()->assemble('1.4.0', $file),
];
