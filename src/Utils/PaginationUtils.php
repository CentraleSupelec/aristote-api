<?php

namespace App\Utils;

use App\Constants;
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

    public function paginationRequestParametersValidator(array $possibleSortFields, string $sort, string $order, int $size, int $page): array
    {
        $errors = [];
        if (!in_array($sort, $possibleSortFields)) {
            $errors[] = sprintf("Sort field '%s' is not supported", $sort);
        }

        if (!in_array(strtolower($order), Constants::SORT_ORDER_OPTIONS)) {
            $errors[] = sprintf("Sort order '%s' is not valid, valid values are : 'DESC' or 'ASC'", $order);
        }

        if ($size < 1) {
            $errors[] = sprintf("Size '%s' is not valid, it should be an integer >= 1", $size);
        }

        if ($page < 1) {
            $errors[] = sprintf("Page '%s' is not valid, it should be an integer >= 1", $page);
        }

        return $errors;
    }
}
