<?php

declare(strict_types=1);

/*
 * Copyright Iain Cambridge 2020-2022.
 *
 * Use of this software is governed by the Business Source License included in the LICENSE file and at https://getparthenon.com/docs/next/license.
 *
 * Change Date: TBD ( 3 years after 2.2.0 release )
 *
 * On the date above, in accordance with the Business Source License, use of this software will be governed by the open source license specified in the LICENSE file.
 */

namespace Parthenon\Export\Exporter;

use Parthenon\Export\ExportRequest;

interface ExporterManagerInterface
{
    public function addExporter(ExporterInterface $exporter): void;

    public function getExporter(ExportRequest $exportRequest): ExporterInterface;

    public function getFormats(): array;
}
