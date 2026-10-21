<?php

declare(strict_types=1);

use WojtJanowski\GlitchTip\Support\CursorPage;

describe('CursorPage::parseLinkHeader', function (): void {
    it('reads cursors only from links that have results', function (): void {
        $header = '<https://glitchtip.test/api/0/organizations/acme/issues/>; rel="previous"; results="false", '
            .'<https://glitchtip.test/api/0/organizations/acme/issues/?cursor=cD0yMDI2>; rel="next"; results="true"; cursor="cD0yMDI2"';

        expect(CursorPage::parseLinkHeader($header))->toBe(['next' => 'cD0yMDI2']);
    });

    it('tolerates the set-wrapped header older self-hosted versions send', function (): void {
        $header = "{'<https://glitchtip.test/api/0/organizations/acme/issues/>; rel=\"previous\"; results=\"false\", "
            ."<https://glitchtip.test/api/0/organizations/acme/issues/?cursor=cD0yMDI2>; rel=\"next\"; results=\"true\"; cursor=\"cD0yMDI2\"'}";

        expect(CursorPage::parseLinkHeader($header))->toBe(['next' => 'cD0yMDI2']);
    });

    it('returns no cursors for an empty header', function (): void {
        expect(CursorPage::parseLinkHeader(''))->toBe([]);
    });

    it('reports no further pages without a fetcher or cursor', function (): void {
        $page = new CursorPage(items: [1, 2]);

        expect($page->hasMore())->toBeFalse()
            ->and($page->next())->toBeNull()
            ->and($page)->toHaveCount(2)
            ->and($page->lazy()->all())->toBe([1, 2]);
    });
});
