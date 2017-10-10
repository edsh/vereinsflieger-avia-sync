<?php
declare(strict_types=1);

namespace LuftsportvereinBacknangHeiningen\VereinsfliegerAviaSync;

use LuftsportvereinBacknangHeiningen\VereinsfliegerDeSdk\Port\Adapter\Service\AmeAviaFlightDataCsvAdapter;

class EdshAviaFlightDataAdapter implements \ArrayAccess
{
    /**
     * @var AmeAviaFlightDataCsvAdapter
     */
    private $originalAdapter;

    public function __construct(AmeAviaFlightDataCsvAdapter $originalAdapter)
    {
        $this->originalAdapter = $originalAdapter;
    }

    public function offsetGet($offset)
    {
        switch ($offset) {
            case AmeAviaFlightDataCsvAdapter::FIELD_FLUGART:
                return $this->flugart();
            default:
                return $this->originalAdapter->offsetGet($offset);
        }
    }

    public function fields(): array
    {
        $self = $this;
        return
            array_map(function($item) use ($self) {
                return $self[$item];
            }, range(0,30));
    }

    public function __toString(): string
    {
        return
            utf8_decode(
                implode(
                    AmeAviaFlightDataCsvAdapter::SEPARATOR,
                    $this->fields()
                )
            );
    }

    /**
     * One of N, S, F, P, L, C, Ü, B, G, W, M, R
     * Must be implemented on one's own since this is client specific.
     */
    private function flugart(): string
    {
        switch ($this->originalAdapter->flightData()->getFtid()) {
            case '1':
                return 'W'; // Werbeflug / Bannerschlepp
            case '2':
                return 'C'; // Checkflug
            case '3':
                return 'F'; // F-Schlepp
            case '4':
                return 'P'; // Passagierflug
            case '5':
                return 'L'; // Leistungsflug
            case '8':
                return 'S'; // Schulflug
            case '10':
                return 'N'; // Privatflug
            case '11':
                return 'B'; // Befähigungsüberprüfung
            case '12':
                return 'Ü'; // Übungsflug / Auffrischungsschulung
        }

        return '';
    }

    public function offsetExists($offset)
    {
        return $this->originalAdapter->offsetExists($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->originalAdapter->offsetSet($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->originalAdapter->offsetUnset($offset);
    }
}
