<?php

namespace App\Services\Sunat;

use Greenter\Model\DocumentInterface;

interface SunatSenderInterface
{
    /**
     * Firma (y según el driver, envía) el documento (comprobante o nota).
     *
     * @return array{estado:string, xml:string, cdrZip:?string, hash:?string, mensaje:string}
     */
    public function emitir(DocumentInterface $document): array;
}
