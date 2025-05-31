
import React, { useState } from 'react';
import { Search, Filter, Download, FileText } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { useApi } from '@/hooks/useApi';
import { pageService, PageFilters } from '@/services/pageService';
import { userService } from '@/services/userService';
import { toast } from '@/components/ui/use-toast';
import CreatePageDialog from '@/components/CreatePageDialog';

const Pages = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [userFilter, setUserFilter] = useState('all');
  const [createDialogOpen, setCreateDialogOpen] = useState(false);

  // Получаем данные страниц через API
  const { data: pagesData, loading, error, refetch } = useApi(
    () => pageService.getPages({
      search: searchTerm || undefined,
      user_id: userFilter !== 'all' ? parseInt(userFilter) : undefined
    }),
    [searchTerm, userFilter]
  );

  // Получаем список пользователей для фильтра
  const { data: usersData } = useApi(() => userService.getUsers({ limit: 100 }), []);

  const pages = pagesData?.pages || [];
  const totalPages = pagesData?.total || 0;
  const users = usersData?.users || [];

  const handleSearch = (value: string) => {
    setSearchTerm(value);
  };

  const handleUserFilter = (value: string) => {
    setUserFilter(value);
  };

  const handlePageCreated = () => {
    refetch();
  };

  const truncateText = (text: string, maxLength: number = 100) => {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
  };

  if (error) {
    toast({
      title: "Ошибка",
      description: `Не удалось загрузить страницы: ${error}`,
      variant: "destructive",
    });
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Страницы</h1>
          <p className="text-muted-foreground">
            Управление текстовыми страницами пользователей
          </p>
        </div>
        <Button onClick={() => setCreateDialogOpen(true)}>
          <FileText className="mr-2 h-4 w-4" />
          Добавить страницу
        </Button>
      </div>

      {/* Filters */}
      <div className="flex flex-col sm:flex-row gap-4">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Поиск по заголовку или содержимому..."
            value={searchTerm}
            onChange={(e) => handleSearch(e.target.value)}
            className="pl-10"
          />
        </div>
        
        <Select value={userFilter} onValueChange={handleUserFilter}>
          <SelectTrigger className="w-[200px]">
            <SelectValue placeholder="Пользователь" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">Все пользователи</SelectItem>
            {users.map((user) => (
              <SelectItem key={user.id} value={user.id.toString()}>
                {user.email}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        <Button variant="outline">
          <Filter className="mr-2 h-4 w-4" />
          Фильтры
        </Button>

        <Button variant="outline">
          <Download className="mr-2 h-4 w-4" />
          Экспорт
        </Button>
      </div>

      {/* Loading state */}
      {loading && (
        <div className="flex justify-center items-center py-8">
          <div className="text-muted-foreground">Загрузка страниц...</div>
        </div>
      )}

      {/* Pages Table */}
      {!loading && (
        <div className="border rounded-lg">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Заголовок</TableHead>
                <TableHead>Пользователь</TableHead>
                <TableHead>Содержимое</TableHead>
                <TableHead>Создано</TableHead>
                <TableHead>Обновлено</TableHead>
                <TableHead>Действия</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {pages.map((page) => (
                <TableRow key={page.id} className="hover:bg-muted/50">
                  <TableCell>
                    <div className="font-medium">{page.title}</div>
                    <div className="text-sm text-muted-foreground">ID: {page.id}</div>
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline">{page.userEmail}</Badge>
                  </TableCell>
                  <TableCell>
                    <div className="max-w-md text-sm text-muted-foreground">
                      {truncateText(page.content)}
                    </div>
                  </TableCell>
                  <TableCell>
                    {new Date(page.createdAt).toLocaleDateString('ru-RU')}
                  </TableCell>
                  <TableCell>
                    {new Date(page.updatedAt).toLocaleDateString('ru-RU')}
                  </TableCell>
                  <TableCell>
                    <Button variant="ghost" size="sm">
                      Действия
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      )}

      {/* Pagination */}
      <div className="flex items-center justify-between">
        <p className="text-sm text-muted-foreground">
          Показано {pages.length} из {totalPages} страниц
        </p>
        <div className="flex space-x-2">
          <Button variant="outline" size="sm" disabled>
            Предыдущая
          </Button>
          <Button variant="outline" size="sm">
            Следующая
          </Button>
        </div>
      </div>

      {/* Create Page Dialog */}
      <CreatePageDialog
        open={createDialogOpen}
        onOpenChange={setCreateDialogOpen}
        onPageCreated={handlePageCreated}
      />
    </div>
  );
};

export default Pages;
