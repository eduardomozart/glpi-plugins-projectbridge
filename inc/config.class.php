<?php

class PluginProjectbridgeConfig extends CommonDBTM
{
    const NAMESPACE = 'projectbridge';
    const MIN_GLPI_VERSION = '9.2';

    public static $table_name = 'glpi_plugin_projectbridge_configs';

    /**
     * Get all recipients of alerts
     *
     * @return array
     */
    public static function getRecipients()
    {
        global $DB;
        static $recipients;

        if ($recipients === null) {
            $recipients = array();

            // todo: use PluginProjectbridgeConfig::find()
            $get_all_recipients_query = "
                SELECT
                    id,
                    user_id
                FROM
                    " . PluginProjectbridgeConfig::$table_name . "
                ORDER BY
                    id ASC
            ";

            $result = $DB->query($get_all_recipients_query);

            if (
                $result
                && $DB->numrows($result)
            ) {
                while ($row = $DB->fetch_assoc($result)) {
                    $user_id = (int) $row['user_id'];
                    $user = new User();
                    $user->getFromDB($user_id);

                    if ($user->getId()) {
                        $default_email = $user->getDefaultEmail();

                        if (!empty($default_email)) {
                            $recipients[(int) $row['id']] = array(
                                'user_id' => $user_id,
                                'name' => $user->fields['name'],
                                'email' => $user->getDefaultEmail(),
                            );
                        }
                    }
                }
            }
        }

        return $recipients;
    }

    /**
     * Notify a recipient
     *
     * @param  string $html_content
     * @param  string $recepient_email
     * @param  string $recepient_name
     * @param  string $subject
     * @return boolean
     */
    public static function notify($html_content, $recepient_email, $recepient_name, $subject)
    {
        global $CFG_GLPI;

        $mailer = new GLPIMailer();

        $mailer->AddCustomHeader('Auto-Submitted: auto-generated');
        // For exchange
        $mailer->AddCustomHeader('X-Auto-Response-Suppress: OOF, DR, NDR, RN, NRN');

        $mailer->SetFrom($CFG_GLPI['admin_email'], $CFG_GLPI['admin_email_name'], false);

        $mailer->isHTML(true);
        $mailer->Body = $html_content . '<br /><br />' . $CFG_GLPI['mailing_signature'];

        $mailer->AddAddress($recepient_email, $recepient_name);
        $mailer->Subject = '[GLPI] ' . $subject;

        return $mailer->Send();
    }
}
