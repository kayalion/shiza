<?php

namespace shiza\manager\ssl;

use shiza\orm\entry\ProjectEnvironmentEntry;

class ManualSslManager implements SslManager {

    public function generateCertificate(ProjectEnvironmentEntry $environment) {
        $environment->setSslExpires(null);

        return false;
    }

}
