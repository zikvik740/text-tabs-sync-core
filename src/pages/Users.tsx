
import React, { useState } from 'react';
import { Search, Filter, Download, UserPlus } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
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
import { userService, UserFilters } from '@/services/userService';
import { toast } from '@/components/ui/use-toast';
import CreateUserDialog from '@/components/CreateUserDialog';

const Users = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [createDialogOpen, setCreateDialogOpen] = useState(false);

  // Получаем данные пользователей через API
  const { data: usersData, loading, error, refetch } = useApi(
    () => userService.getUsers({
      search: searchTerm || undefined,
      status: statusFilter !== 'all' ? statusFilter : undefined
    }),
    [searchTerm, statusFilter]
  );

  const users = usersData?.users || [];
  const totalUsers = usersData?.total || 0;

  const getStatusBadge = (status: string) => {
    if (status === 'verified') {
      return <Badge className="bg-green-100 text-green-800 hover:bg-green-100">Подтвержден</Badge>;
    }
    if (status === 'blocked') {
      return <Badge className="bg-red-100 text-red-800 hover:bg-red-100">Заблокирован</Badge>;
    }
    return <Badge variant="secondary">Ожидает</Badge>;
  };

  const getInitials = (email: string) => {
    return email.substring(0, 2).toUpperCase();
  };

  const handleSearch = (value: string) => {
    setSearchTerm(value);
  };

  const handleStatusFilter = (value: string) => {
    setStatusFilter(value);
  };

  const handleUserCreated = () => {
    refetch();
  };

  if (error) {
    toast({
      title: "Ошибка",
      description: `Не удалось загрузить пользователей: ${error}`,
      variant: "destructive",
    });
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Пользователи</h1>
          <p className="text-muted-foreground">
            Управление пользователями расширения Text Tabs
          </p>
        </div>
        <Button onClick={() => setCreateDialogOpen(true)}>
          <UserPlus className="mr-2 h-4 w-4" />
          Добавить пользователя
        </Button>
      </div>

      {/* Filters */}
      <div className="flex flex-col sm:flex-row gap-4">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Поиск по email..."
            value={searchTerm}
            onChange={(e) => handleSearch(e.target.value)}
            className="pl-10"
          />
        </div>
        
        <Select value={statusFilter} onValueChange={handleStatusFilter}>
          <SelectTrigger className="w-[180px]">
            <SelectValue placeholder="Статус" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">Все статусы</SelectItem>
            <SelectItem value="verified">Подтвержденные</SelectItem>
            <SelectItem value="pending">Ожидающие</SelectItem>
            <SelectItem value="blocked">Заблокированные</SelectItem>
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
          <div className="text-muted-foreground">Загрузка пользователей...</div>
        </div>
      )}

      {/* Users Table */}
      {!loading && (
        <div className="border rounded-lg">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Пользователь</TableHead>
                <TableHead>Статус</TableHead>
                <TableHead>Страниц</TableHead>
                <TableHead>Регистрация</TableHead>
                <TableHead>Последняя активность</TableHead>
                <TableHead>Действия</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {users.map((user) => (
                <TableRow key={user.id} className="hover:bg-muted/50">
                  <TableCell>
                    <div className="flex items-center space-x-3">
                      <Avatar>
                        <AvatarFallback>{getInitials(user.email)}</AvatarFallback>
                      </Avatar>
                      <div>
                        <p className="font-medium">{user.email}</p>
                        <p className="text-sm text-muted-foreground">ID: {user.id}</p>
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    {getStatusBadge(user.status)}
                  </TableCell>
                  <TableCell>
                    <span className="font-medium">{user.pagesCount}</span>
                  </TableCell>
                  <TableCell>
                    {new Date(user.createdAt).toLocaleDateString('ru-RU')}
                  </TableCell>
                  <TableCell>
                    {new Date(user.lastActive).toLocaleDateString('ru-RU')}
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
          Показано {users.length} из {totalUsers} пользователей
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

      {/* Create User Dialog */}
      <CreateUserDialog
        open={createDialogOpen}
        onOpenChange={setCreateDialogOpen}
        onUserCreated={handleUserCreated}
      />
    </div>
  );
};

export default Users;
