<?php

namespace App\Utils;

use Knp\Component\Pager\Pagination\PaginationInterface;

class PaginationUtils
{
    public function parsePagination(PaginationInterface $pagination): array
    {
        return [
            'content' => $pagination->getItems(),
            'totalElements' => $pagination->getTotalItemCount(),
            'currentPage' => $pagination->getCurrentPageNumber(),
            'isLastPage' => $this->isLastPage($pagination->getCurrentPageNumber(), $pagination->getItemNumberPerPage(), $pagination->getTotalItemCount()),
        ];
    }

    private function isLastPage(int $page, int $size, int $totalItemCount): bool
    {
        $lastPage = (int) ceil($totalItemCount / $size);

        return $page === $lastPage;
    }
}
