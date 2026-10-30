import { type SyntheticEvent, useEffect, useRef, useState } from 'react';
import Pagination from '@/react/common/Pagination';
import type { ApplicationItem } from '../types/ApplicationItem';
import { ApplicationCard } from './ApplicationCard';

export const PAGE_SIZE = 5;

type ApplicationListProps = {
  applications: ApplicationItem[];
  // Changes whenever the search, filter or sort changes so the list returns to the first page.
  resetKey?: string;
};

export const ApplicationList = ({ applications, resetKey }: ApplicationListProps) => {
  const [page, setPage] = useState(1);
  const [focusFirstItem, setFocusFirstItem] = useState(false);
  const listRef = useRef<HTMLDivElement>(null);
  const totalPages = Math.ceil(applications.length / PAGE_SIZE);
  const currentPage = Math.min(page, Math.max(totalPages, 1));

  useEffect(() => {
    setPage(1);
  }, [resetKey]);

  // Move focus to the first item of the new page so keyboard and screen reader
  // users land on the content that changed.
  useEffect(() => {
    if (!focusFirstItem) {
      return;
    }
    const firstItem = listRef.current?.querySelector<HTMLElement>('.application-list__item');
    if (firstItem) {
      firstItem.setAttribute('tabindex', '-1');
      firstItem.focus();
    }
    setFocusFirstItem(false);
  }, [focusFirstItem]);

  const updatePage = (e: SyntheticEvent, newPage: number) => {
    e.preventDefault();
    setPage(newPage);
    setFocusFirstItem(true);
  };

  if (!applications.length) {
    return null;
  }

  const visibleApplications = applications.slice((currentPage - 1) * PAGE_SIZE, currentPage * PAGE_SIZE);

  return (
    <>
      <div ref={listRef}>
        {visibleApplications.map((application) => (
          <ApplicationCard key={application.applicationNumber} application={application} />
        ))}
      </div>
      {totalPages > 1 && (
        <Pagination currentPage={currentPage} pages={5} totalPages={totalPages} updatePage={updatePage} />
      )}
    </>
  );
};
