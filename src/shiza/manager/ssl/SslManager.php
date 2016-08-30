<?php

namespace shiza\manager\ssl;

use shiza\orm\entry\ProjectEnvironmentEntry;

interface SslManager {

    public function generateCertificate(ProjectEnvironmentEntry $environment);

}
