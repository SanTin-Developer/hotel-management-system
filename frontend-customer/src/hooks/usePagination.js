import { useEffect, useState } from "react";

export function usePagination({ page = 1, totalPages = 1, onChange } = {}) {
  const [currentPage, setCurrentPage] = useState(page);

  useEffect(() => {
    // sync current page when the source page changes
    // eslint-disable-next-line react-hooks/set-state-in-effect
    setCurrentPage(page);
  }, [page]);

  const goToPage = (nextPage) => {
    if (nextPage < 1 || nextPage > totalPages) return;
    setCurrentPage(nextPage);
    if (onChange) onChange(nextPage);
  };

  const nextPage = () => goToPage(currentPage + 1);
  const prevPage = () => goToPage(currentPage - 1);

  return {
    currentPage,
    totalPages,
    goToPage,
    nextPage,
    prevPage,
  };
}