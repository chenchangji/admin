<template>
  <page-content class="dashboard-container">

    <a-row :gutter="[16, 16]">
      <!-- 柱状图 -->
      <a-col :xs="24" :sm="24" :md="12" :lg="12">
        <a-card title="素材分类统计" class="chart-card">
          <div ref="barChart" class="chart"></div>
        </a-card>
      </a-col>

      <!-- 饼状图 -->
      <a-col :xs="24" :sm="24" :md="12" :lg="12">
        <a-card title="产品素材占比" class="chart-card">
          <div ref="pieChart" class="chart"></div>
        </a-card>
      </a-col>

      <!-- 双轴图 -->
      <a-col :span="24">
        <a-card title="合成视频和下载量增长趋势" class="chart-card">
          <div ref="dualChart" class="chart"></div>
        </a-card>
      </a-col>
    </a-row>
  </page-content>
</template>

<script>
import PageContent from '@c/PageContent';
import * as echarts from 'echarts';
import {
  getCountByClass,
  getCountByProduct,
  getVideoCount,
} from '@/api/admin-materials'

export default {
  name: 'Dashboard',
  components: {
    PageContent
  },
  mounted() {
    this.initCharts();
    window.addEventListener('resize', this.handleResize);
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.handleResize);
    if (this.barChart) this.barChart.dispose();
    if (this.pieChart) this.pieChart.dispose();
    if (this.dualChart) this.dualChart.dispose();
  },
  methods: {
     async initCharts() {
      // 初始化柱状图
      this.barChart = echarts.init(this.$refs.barChart);
      
      // 初始化饼图
      this.pieChart = echarts.init(this.$refs.pieChart);
      
      // 初始化双轴图
      this.dualChart = echarts.init(this.$refs.dualChart);
      
      // 加载柱状图数据
      await this.loadBarData();

      // 加载饼图数据
      await this.loadPieData();

      // 加载双轴图数据
      await this.loadDualData();
    },

    async loadBarData() {
      this.loading = true;
      try {
        const res = await getCountByClass();
        this.barData = res.data; // 假设API返回格式: { categories: [], values: [] }
        this.barChart.setOption(this.getBarOption());
      } catch (error) {
        console.error('获取柱状图数据失败:', error);
        // 使用默认数据作为fallback
        this.barData = {
          categories: ['营销内容', '痛点/症状', '产品背书', '引导购买'],
          values: [0, 0, 0, 0]
        };
        this.barChart.setOption(this.getBarOption());
      } finally {
        this.loading = false;
      }
    },

    async loadPieData() {
      this.loading = true;
      try {
        const res = await getCountByProduct();
        this.pieData = res.data;  
        this.pieChart.setOption(this.getPieOption());
      } catch (error) {
        console.error('获取饼状图数据失败:', error);
        // 使用默认数据作为fallback
        this.pieData = {
          categories: ['营销内容', '痛点/症状', '产品背书', '引导购买'],
          values: [0, 0, 0, 0]
        };
        this.pieChart.setOption(this.getPieOption());
      } finally {
        this.loading = false;
      }
    },

    async loadDualData() {
      this.loading = true;
      try {
        const res = await getVideoCount();
        this.dualData = res.data; 
        this.dualChart.setOption(this.getDualOption());
      } catch (error) {
        console.error('获取双折图数据失败:', error);
        // 使用默认数据作为fallback
        this.dualData = {
          values: [0, 0, 0, 0]
        };
        this.dualChart.setOption(this.getDualOption());
      } finally {
        this.loading = false;
      }
    },
    
    handleResize() {
      if (this.barChart) this.barChart.resize();
      if (this.pieChart) this.pieChart.resize();
      if (this.dualChart) this.dualChart.resize();
    },
    
    getBarOption() {
      const values = this.barData || [0, 0, 0, 0];
      return {
        tooltip: {
          trigger: 'axis',
          axisPointer: {
            type: 'shadow'
          }
        },
        grid: {
          left: '3%',
          right: '4%',
          bottom: '3%',
          containLabel: true
        },
        xAxis: [
          {
            type: 'category',
            data: ['营销内容', '痛点/症状', '产品背书', '引导购买'],
            axisTick: {
              alignWithLabel: true
            }
          }
        ],
        yAxis: [
          {
            type: 'value'
          }
        ],
        series: [
          {
            name: '数量',
            type: 'bar',
            barWidth: '60%',
            data: values,
            itemStyle: {
              color: (params) => {
                const colors = ['#1890FF', '#13C2C2', '#722ED1', '#F5222D'];
                return colors[params.dataIndex];
              }
            }
          }
        ]
      };
    },
    
    getPieOption() {
      const values = this.pieData || [0, 0, 0, 0];
      return {
        tooltip: {
          trigger: 'item',
          formatter: '{a} <br/>{b}: {c} ({d}%)'
        },
        legend: {
          orient: 'horizontal',
          bottom: 'bottom',
          data: ['舒筋健腰丸', '清血八味片', '咽康']
        },
        series: [
          {
            name: '产品素材占比',
            type: 'pie',
            radius: ['40%', '70%'],
            avoidLabelOverlap: false,
            itemStyle: {
              borderRadius: 10,
              borderColor: '#fff',
              borderWidth: 2
            },
            label: {
              show: false,
              position: 'center'
            },
            emphasis: {
              label: {
                show: true,
                fontSize: '16',
                fontWeight: 'bold'
              }
            },
            labelLine: {
              show: false
            },
            
            data: values,
            color: ['#1890FF', '#13C2C2', '#722ED1']
          }
        ]
      };
    },
    
    getDualOption() {
      const data = this.dualData;
      return {
        tooltip: {
          trigger: 'axis',
          axisPointer: {
            type: 'cross',
            crossStyle: {
              color: '#999'
            }
          }
        },
        legend: {
          data: ['下载次数', '合成视频数']
        },
        grid: {
          left: '3%',
          right: '4%',
          bottom: '3%',
          containLabel: true
        },
        xAxis: [
          {
            type: 'category',
            data: data.weeks,
            axisPointer: {
              type: 'shadow'
            }
          }
        ],
        yAxis: [
          {
            type: 'value',
            name: '下载次数',
            min: 0,
            max: 100,
            interval: 50
          },
          {
            type: 'value',
            name: '合成视频数',
            min: 0,
            max: 100,
            interval: 200
          }
        ],
        series: [
          {
            name: '下载次数',
            type: 'bar',
            data: data.total_downloads,
            itemStyle: {
              color: '#1890FF'
            }
          },
          {
            name: '合成视频数',
            type: 'line',
            yAxisIndex: 1,
            data: data.total,
            itemStyle: {
              color: '#F5222D'
            }
          }
        ]
      };
    }
  }
};
</script>

<style scoped>
.dashboard-container {
  padding: 16px;
}

.welcome-row {
  margin-bottom: 24px;
}

.welcome-title {
  text-align: center;
  font-weight: 500;
  color: rgba(0, 0, 0, 0.85);
  margin-bottom: 0;
}

.chart-card {
  border-radius: 4px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
  margin-bottom: 16px;
  overflow: hidden;
}

.chart-card :deep(.ant-card-head) {
  background: #fafafa;
  border-bottom: 1px solid #e8e8e8;
  font-weight: 600;
}

.chart-card :deep(.ant-card-body) {
  padding: 16px;
}

.chart {
  height: 300px;
  width: 100%;
}

/* 双轴图高度稍大 */
.chart-card:nth-child(3) .chart {
  height: 350px;
}
</style>