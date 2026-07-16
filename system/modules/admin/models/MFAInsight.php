<?php

class MFAInsight extends InsightBaseClass
{
    public $name = "MFA Insight";
    public $description = "Shows uptake of Multi-factor authentication";

    //Displays Filters to select user
    public function getFilters(Web $w, $parameters = []): array
    {
        return [];
    }

    //Displays insights for selections made in the above "Options"
    public function run(Web $w, $parameters = []): array
    {
        $users = AdminService::getInstance($w)->getUsers(['user.is_external' => 0, 'user.is_group' => 0, 'user.is_deleted' => 0]);

        $users_with_mfa = array_filter($users, function ($user) {
            return $user->is_mfa_enabled;
        });

        $users_without_mfa = array_filter($users, function ($user) {
            return !$user->is_mfa_enabled;
        });

        $passkeys = AuthService::getInstance($w)
            ->_db->sql("SELECT DISTINCT user_id FROM web_authn_credential")
            ->fetchAll();

        $users_with_passkeys = array_column($passkeys, "user_id");

        $users_without_any = array_filter(
            $users_without_mfa,
            fn($val) => !array_find(
                $users_with_passkeys,
                fn($id) => $val->id == $id
            )
        );

        $results = [];
        $results[] = new InsightReportInterface(
            'User MFA summary',
            ['# with TOTP', "# with Passkey", '# without any MFA'],
            [[count($users_with_mfa), count($users_with_passkeys), count($users_without_any)]]
        );

        $mfa_breakdown = [];
        foreach ($users_without_any as $no_mfa) {
            $contact = $no_mfa->getContact();

            $mfa_breakdown[] = [
                StringSanitiser::sanitise($no_mfa->login),
                !empty($contact) ? StringSanitiser::sanitise($contact->getFullName()) : 'No contact object found',
                !empty($contact) ? StringSanitiser::sanitise($contact->email) : 'No contact object found',
                formatDate($no_mfa->dt_lastlogin, 'Y-m-d')
            ];
        }

        $results[] = new InsightReportInterface('No MFA breakdown', ['Login', 'Name', 'Email', 'Last Login'], $mfa_breakdown);

        return $results;
    }
}
