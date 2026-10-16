<?php

namespace App\Company\Gateway;

use App\Company\Helper\SiretHelper;
use App\Company\Model\RegistryEstablishment;
use NeoPHP\Component\HttpClient\HttpClientManagerInterface;
use Throwable;

class CompanyRegistryGateway
{
    private const ENDPOINT = 'https://recherche-entreprises.api.gouv.fr/search';

    public function __construct(
        private HttpClientManagerInterface $http
    ) {
    }

    public function findBySiret(string $siret): ?RegistryEstablishment
    {
        $siret = SiretHelper::normalize($siret);

        try {
            $response = $this->http->get(self::ENDPOINT, ['query' => ['q' => $siret, 'per_page' => 1], 'timeout' => 5]);

            if (!$response->isSuccessful()) {
                return null;
            }

            $data = $response->toArray(false);
        } catch (Throwable) {
            return null;
        }

        foreach ($data['results'] ?? [] as $company) {
            foreach (array_merge($company['matching_etablissements'] ?? [], [$company['siege'] ?? []]) as $establishment) {
                if (($establishment['siret'] ?? null) === $siret) {
                    return $this->map($company, $establishment);
                }
            }
        }

        return null;
    }

    private function map(array $company, array $establishment): RegistryEstablishment
    {
        return new RegistryEstablishment(
            siret: $establishment['siret'],
            siren: (string) ($company['siren'] ?? SiretHelper::sirenOf($establishment['siret'])),
            legalName: (string) ($company['nom_raison_sociale'] ?? $company['nom_complet'] ?? ''),
            brandName: $establishment['liste_enseignes'][0] ?? null,
            address: $establishment['adresse'] ?? null,
            postalCode: $establishment['code_postal'] ?? null,
            locality: $establishment['libelle_commune'] ?? null,
            apeCode: $establishment['activite_principale'] ?? $company['activite_principale'] ?? null,
            apeLabel: $company['libelle_activite_principale'] ?? null,
            active: ($establishment['etat_administratif'] ?? 'A') === 'A',
            latitude: isset($establishment['latitude']) ? (float) $establishment['latitude'] : null,
            longitude: isset($establishment['longitude']) ? (float) $establishment['longitude'] : null,
        );
    }
}